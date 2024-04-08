# Set up a proxy route to a Git server

```mermaid
flowchart LR
    Workbench[Workbench server] -->|"Request: git ...\nAuth: HTTP Basic, IP filter, etc."| Route
    subgraph Proxy
        Route[Proxy route] -.-> Connection[Data connection]
    end
    Connection -->|"Request: git ...\nAuth: GitLab-Token"| Git[Git server]
```

Git proxy routes allow to conceal all potentially sesitive Git access information from a workbench server. 

## Overview

Steps to set up a proxy connection to a Git repo:

1. Add an access key to the Git repo or a repo group to make sure the proxy can only access that particular repo or group
	- Only read/write repository permissions!
2. Add a proxy route to this connection
	- Add an HTTP connection to the proxy workbench metamodel with the repo HTTPS URL and the key from above
3. Configure authorization policies for the route 
	- Add a user role
	- Add a user
4. Clone the repo on the client using the URL of the proxy and the `Authorization` HTTP header as shown below

## Add a route

## Clone the repo

`git clone https://<username>:@domain.com/api/proxy/git/myrepo.git c:\path_to_workbench\vendor\myvendor\myapp -c http.extraHeader="Authorization: Basic <username:password (as Base64)>"`