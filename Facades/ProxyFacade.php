<?php
namespace axenox\Proxy\Facades;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\DataTypes\StringDataType;
use exface\Core\Facades\AbstractHttpFacade\Middleware\AuthenticationMiddleware;
use exface\Core\Exceptions\Facades\FacadeRoutingError;
use axenox\Proxy\Facades\RequestHandlers\DefaultProxy;

/**
 * This facade acts as a proxy: it fetches and passes along data located at the URI in the request parameter "url".
 * 
 * This can be use for cross-origin AJAX requests or HTTP-requests on an HTTPS-server. Concider the following examples:
 * 
 * The client uses a secure HTTPS-conection, but the data source works with HTTP. If the data fetched from the data
 * source contains a resource URI (e.g. an image), the `Image` widget would normally just tell the client browser
 * to fetch the image from the given URI. This will fail because the browser would not want an HTTP-image in an
 * HTTPS-page. Since we trust the data source, a solution may be create a proxy route pointing to the real server with
 * images and change the image path to: `https://my.plattform.serv/api/proxy/my_route/path_to_image.jpg`. Now the browser 
 * will request a safe resource from our server, which in-turn will fetch it from the data source and pass the result on 
 * to the browser.
 * 
 * A similar situation occurs if a data source checks the origin of requests and does not allow certain resource
 * types (again, images), to be requested separately: e.g. an image cannot be loaded if the corresponding web page
 * was not requested from the same origin previously. When fetching data from websites with this kind of restrictions 
 * via the UrlDataConnector, we can't let the browser fetch resources directly, but can tunnel requests through
 * the ProxyFacade, which would be the same origin as the data reading request from the connector.
 * 
 * Last, but not least, using the ProxyFacade, an additional caching layer may be implemented, allowing to reduce
 * the load on external servers or even fetch resources if the original data source is not accessible (e.g. down). 
 * 
 * TODO The caching functionality is not implemented yet.
 * 
 * ## Security
 * 
 * **WARNING**: the use of the proxy facade is always a potential security risc! Avoid to use it in production!
 * 
 * The proxy facade requires policies for the `HttpRequestAuthorizationPoint` as all HTTP facades do. Thus, you can
 * (and should!) create policies for every proxy route.
 * 
 * Also make sure to define routes that only allow a very narrow scope of requests.
 * 
 * @author Andrej Kabachnik
 *
 */
class ProxyFacade extends AbstractHttpFacade
{    
    private $routesData = null;
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::createResponse()
     */
    protected function createResponse(ServerRequestInterface $request) : ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $path = StringDataType::substringAfter($path, $this->getUrlRouteDefault() . '/', '');
        $routeModel = $this->getRouteData($path);
        $handlerClass = $routeModel['HANDLER_CLASS'] ?? '\\' . DefaultProxy::class;
        $handler = new $handlerClass($this, $routeModel);
        return $handler->handle($request);
    }
    
    /**
     * 
     * @param string $uri
     * @return string
     */
    public function getProxyUrl(string $uri) : string
    {
        return $this->buildUrlToFacade() . '/' . urlencode($uri);
    }

    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::getUrlRouteDefault()
     */
    public function getUrlRouteDefault(): string
    {
        return 'api/proxy';
    }
    
    protected function getRouteData(string $route) : array
    {
        if ($this->routesData === null) {
            $ds = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'axenox.Proxy.ROUTE');
            $ds->getColumns()->addMultiple([
                'UID',
                'ROUTE_URL',
                'ROUTE_REGEX_FLAG',
                'DESTINATION_URL',
                'DESTINATION_CONNECTION',
                'HANDLER_CLASS',
                'HANDLER_UXON'
            ]);
            $ds->dataRead();
            $this->routesData = $ds;
        }
        
        foreach ($this->routesData->getRows() as $row) {
            if ($row['ROUTE_URL'] && StringDataType::startsWith($route, $row['ROUTE_URL'])) {
                return $row;
            }
        }
        
        throw new FacadeRoutingError('No route configuration found for "' . $route . '"');
    }
    
    protected function getMiddleware() : array
    {
        $middleware = parent::getMiddleware();
        $middleware[] = new AuthenticationMiddleware(
            $this,
            [
                [AuthenticationMiddleware::class, 'extractBasicHttpAuthToken']
            ]
        );
        
        return $middleware;
    }
}