# Set up a proxy route to a Git server

## Overview

Steps to set up a proxy connection to a Git repo:

1. Add an access key to the Git repo or a repo group to make sure the proxy can only access that particular repo or group
	- Only read/write repository permissions!
2. Add an HTTP connection to the proxy workbench metamodel with the repo HTTPS URL and the key from above
3. Add a Route to this connection
4. Configure permissions for the route 
5. Clone the repo on the client using the URL of the proxy and the `Authorization` HTTP header as shown below

## Adding a route

## Cloning a repo

`git clone https://<username>:@domain.com/api/proxy/git/myrepo.git c:\path_to_workbench\vendor\myvendor\myapp -c http.extraHeader="Authorization: Basic <username:password (as Base64)>"`