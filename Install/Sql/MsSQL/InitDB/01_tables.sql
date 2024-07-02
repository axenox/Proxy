IF OBJECT_ID ('dbo.exf_proxy_route', N'U') IS NULL 
CREATE TABLE dbo.exf_proxy_route (
  oid binary(16) NOT NULL,
  created_on datetime NOT NULL,
  modified_on datetime NOT NULL,
  created_by_user_oid binary(16) DEFAULT NULL,
  modified_by_user_oid binary(16) DEFAULT NULL,
  name nvarchar(100) NOT NULL,
  alias nvarchar(100) NOT NULL,
  app_oid binary(16) NULL,
  description nvarchar(max),
  route_url nvarchar(400) NOT NULL,
  route_regex_flag tinyint NOT NULL DEFAULT '0',
  destination_url nvarchar(400) NOT NULL,
  destination_connection binary(16) NULL,
  handler_class nvarchar(400) NULL,
  handler_uxon nvarchar(max),
  CONSTRAINT PK_proxy_routes PRIMARY KEY (oid)
);
