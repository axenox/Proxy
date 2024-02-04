<?php
namespace axenox\Proxy\Facades\RequestHandlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use axenox\Proxy\Facades\ProxyFacade;
use exface\Core\DataTypes\StringDataType;
use exface\Core\Interfaces\iCanBeConvertedToUxon;
use exface\Core\CommonLogic\Traits\ImportUxonObjectTrait;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\DataTypes\BooleanDataType;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use exface\Core\Factories\DataConnectionFactory;
use exface\UrlDataConnector\Interfaces\UrlConnectionInterface;
use exface\Core\Interfaces\WorkbenchInterface;
use exface\Core\CommonLogic\Debugger\HttpMessageDebugWidgetRenderer;

class DefaultProxy implements RequestHandlerInterface, iCanBeConvertedToUxon
{
    use ImportUxonObjectTrait;
    
    private $facade = null;
    
    private $routeModel = null;
    
    private $urldecodeToPath = false;
    
    private $requestHeadersToRemove = [];
    
    private $requestHeadersToReplace = [];
    
    private $responseHeadersToRemove = ['Transfer-Encoding'];
    
    private $responseHeadersToReplace = [];
    
    public function __construct(ProxyFacade $facade, array $routeModel)
    {
        $this->facade = $facade;   
        $this->routeModel = $routeModel;
        if (null !== $json = $routeModel['HANDLER_UXON']) {
            $this->importUxonObject(UxonObject::fromJson($json));
        }
    }
    
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = StringDataType::substringAfter($request->getUri()->getPath(), $this->getFacade()->getUrlRouteDefault() . '/', '');
        
        $routeBase = $this->getFromUrl();
        $remotePath = StringDataType::substringAfter($path, $routeBase);
        if ($this->getUrldecodeDestinationPath()) {
            $remotePath = urldecode($remotePath);
        }
        
        $method = $request->getMethod();
        if ($method !== 'GET' && $method !== 'OPTIONS') {
            $body = $request->getBody();
        } else {
            $body = null;
        }
        
        $remoteQuery = $request->getUri()->getQuery();
        $remoteUrl = $this->getToUrl($path) . '/' . $remotePath . ($remoteQuery ? '?' . $remoteQuery : '');
        $remoteUrl = ltrim($remoteUrl, '/');
        $remoteUrl = str_replace('//', '/', $remoteUrl);
        
        $connection = $this->getRouteConnection($path);
        $remoteRequest = new Request($method, $remoteUrl, $this->getRequestHeaders($request), $body, $request->getProtocolVersion());
        $this->getWorkbench()->getLogger()->debug('Proxy request to "' . $remotePath . '" sent', [], new HttpMessageDebugWidgetRenderer($remoteRequest));
        $remoteResponse = $connection->sendRequest($remoteRequest);
        $this->getWorkbench()->getLogger()->debug('Proxy response (' . $remoteResponse->getStatusCode() . ') from "' . $remotePath . '" received', [], new HttpMessageDebugWidgetRenderer($remoteRequest, $remoteResponse));
        
        $responseHeaders = $this->getResponseHeaders($remoteResponse);
        // TODO Merge haders from the target response and the security middleware in case the
        // latter added something important.
        
        $response = new Response(
            $remoteResponse->getStatusCode(), 
            $responseHeaders, 
            $remoteResponse->getBody(), 
            $remoteResponse->getProtocolVersion(), 
            $remoteResponse->getReasonPhrase()
        );
        
        return $response;
    }
    
    /**
     * 
     * @param ServerRequestInterface $request
     * @return string[][]
     */
    protected function getRequestHeaders(ServerRequestInterface $request) : array
    {
        $headers = $request->getHeaders();
        foreach ($this->getRemoveRequestHeaders() as $header) {
            foreach (array_keys($headers) as $h) {
                if (strcasecmp($h, $header) === 0) {
                    unset ($headers[$h]);
                }
            }
        }
        foreach ($this->getReplaceRequestHeaders() as $header => $value) {
            foreach (array_keys($headers) as $h) {
                if (strcasecmp($h, $header) === 0) {
                    unset ($headers[$h]);
                }
            }
            $headers[$header] = $value;
        }
        return $headers;
    }
    
    /**
     * 
     * @param ResponseInterface $response
     * @return string[][]
     */
    protected function getResponseHeaders(ResponseInterface $response) : array
    {
        $headers = $response->getHeaders();
        foreach ($this->getRemoveResponseHeaders() as $header) {
            foreach (array_keys($headers) as $h) {
                if (strcasecmp($h, $header) === 0) {
                    unset ($headers[$h]);
                }
            }
        }
        foreach ($this->getReplaceResponseHeaders() as $header => $value) {
            foreach (array_keys($headers) as $h) {
                if (strcasecmp($h, $header) === 0) {
                    unset ($headers[$h]);
                }
            }
            $headers[$header] = $value;
        }
        return $headers;
    }
    
    /**
     * 
     * @return bool
     */
    protected function getUrldecodeDestinationPath() : bool
    {
        return $this->urldecodeToPath;
    }
    
    /**
     * Set to TRUE to url-decode the URI path trasferred from the from-url to the to-url
     * 
     * @uxon-property urldecode_destination_path
     * @uxon-type bool
     * @uxon-default false
     * 
     * @param bool $value
     * @return DefaultProxy
     */
    protected function setUrldecodeDestinationPath(bool $value) : DefaultProxy
    {
        $this->urldecodeToPath = $value;
        return $this;
    }
    
    /**
     *
     * @return string[]|string[][]
     */
    function getRemoveRequestHeaders() : array
    {
        return $this->requestHeadersToRemove;
    }
    
    /**
     * Array of HTTP header names to remove from the received request (all other headers will be passed through)
     * 
     * @uxon-property remove_request_headers
     * @uxon-type object
     * @uxon-template ["Authorization"]
     * @uxon-default []
     * 
     * @param UxonObject $value
     * @return DefaultProxy
     */
    protected function setRemoveRequestHeaders(UxonObject $value) : DefaultProxy
    {
        $this->requestHeadersToRemove = $value->toArray();
        return $this;
    }
    
    /**
     * 
     * @return string[]|string[][]
     */
    protected function getReplaceRequestHeaders() : array
    {
        return $this->requestHeadersToReplace;
    }
    
    /**
     * HTTP headers to set on the on the response replacing those in the original response from the destination
     * 
     * @uxon-property replace_request_headers
     * @uxon-type object
     * @uxon-template {"Content-Type": ""}
     * @uxon-default {}
     * 
     * @param UxonObject $value
     * @return DefaultProxy
     */
    protected function setReplaceRequestHeaders(UxonObject $value) : DefaultProxy
    {
        $this->requestHeadersToReplace = $value->toArray();
        return $this;
    }
    
    /**
     * 
     * @return string[]|string[][]
     */
    protected function getRemoveResponseHeaders() : array
    {
        return $this->responseHeadersToRemove;
    }
    
    /**
     * Array of HTTP header names to remove from the response of the destination before relaying it
     *
     * @uxon-property remove_response_headers
     * @uxon-type object
     * @uxon-template ["Transfer-Encoding"]
     * @uxon-default ["Transfer-Encoding"]
     *
     * @param UxonObject $value
     * @return DefaultProxy
     */
    protected function setRemoveResponseHeaders(UxonObject $value) : DefaultProxy
    {
        $this->responseHeadersToRemove = $value->toArray();
        return $this;
    }
    
    /**
     *
     * @return array
     */
    protected function getReplaceResponseHeaders() : array
    {
        return $this->responseHeadersToReplace;
    }
    
    /**
     * HTTP headers to set on the on the resulting response replacing those in the original response
     *
     * @uxon-property replace_response_headers
     * @uxon-type object
     * @uxon-template {"Content-Type": ""}
     *
     * @param UxonObject $value
     * @return DefaultProxy
     */
    protected function setReplaceResponseHeaders(UxonObject $value) : DefaultProxy
    {
        $this->responseHeadersToReplace = $value->toArray();
        return $this;
    }
    
    protected function isUsingRegex() : bool
    {
        return BooleanDataType::cast($this->routeModel['ROUTE_REGEX_FLAG']);
    }
    
    protected function getToUrl() : string
    {
        return $this->routeModel['DESTINATION_URL'];
    }
    
    protected function getFromUrl() : string
    {
        return $this->routeModel['ROUTE_URL'];
    }
    
    protected function getFacade() : ProxyFacade
    {
        return $this->facade;
    }
    
    protected function getRouteConnection() : UrlConnectionInterface
    {
        $uid = $this->routeModel['DESTINATION_CONNECTION'];
        if ($uid !== null) {
            $selector = $uid;
        } else {
            $selector = 'exface.UrlDataConnector.URL_READONLY';
        }
        return DataConnectionFactory::createFromModel($this->getWorkbench(), $selector);
    }
    
    public function exportUxonObject()
    {
        // TODO
        return new UxonObject();
    }
    
    protected function getWorkbench() : WorkbenchInterface
    {
        return $this->facade->getWorkbench();
    }
}