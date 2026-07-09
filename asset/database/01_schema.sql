IF DB_ID(N'BSPI_Asset_Manager') IS NULL CREATE DATABASE [BSPI_Asset_Manager];
GO
USE [BSPI_Asset_Manager];
GO

IF OBJECT_ID(N'Settings', N'U') IS NOT NULL DROP TABLE [Settings];
GO

CREATE TABLE [Settings] (
  [SITES] NVARCHAR(MAX) NULL,
  [ACTIVE] NVARCHAR(MAX) NULL,
  [DEPARTMENTS / OFFICES] NVARCHAR(MAX) NULL,
  [ASSET CATEGORY] NVARCHAR(MAX) NULL,
  [PREFIX] NVARCHAR(MAX) NULL,
  [DEFAULT EOL (YEARS)] NVARCHAR(MAX) NULL,
  [ASSET STATUSES] NVARCHAR(MAX) NULL,
  [DEPLOYMENT STATUSES] NVARCHAR(MAX) NULL,
  [ACTIVE FLAGS] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'Asset_Counters', N'U') IS NOT NULL DROP TABLE [Asset_Counters];
GO

CREATE TABLE [Asset_Counters] (
  [SITE] NVARCHAR(MAX) NULL,
  [LAST STATION NO USED] NVARCHAR(MAX) NULL,
  [ASSET CATEGORY] NVARCHAR(MAX) NULL,
  [PREFIX] NVARCHAR(MAX) NULL,
  [LAST ASSET NO USED] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'Deployments', N'U') IS NOT NULL DROP TABLE [Deployments];
GO

CREATE TABLE [Deployments] (
  [Main Asset Number] NVARCHAR(MAX) NULL,
  [Reporting Branch] NVARCHAR(MAX) NULL,
  [User] NVARCHAR(MAX) NULL,
  [Office] NVARCHAR(MAX) NULL,
  [System Unit Code] NVARCHAR(MAX) NULL,
  [Monitor Code] NVARCHAR(MAX) NULL,
  [Mouse Code] NVARCHAR(MAX) NULL,
  [Keyboard Code] NVARCHAR(MAX) NULL,
  [UPS Code] NVARCHAR(MAX) NULL,
  [Printer Code] NVARCHAR(MAX) NULL,
  [Laptop Code] NVARCHAR(MAX) NULL,
  [Date Deployed] NVARCHAR(MAX) NULL,
  [Date Returned] NVARCHAR(MAX) NULL,
  [Active] NVARCHAR(MAX) NULL,
  [Deployment Status] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Created At] NVARCHAR(MAX) NULL,
  [Updated At] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'System Units', N'U') IS NOT NULL DROP TABLE [System Units];
GO

CREATE TABLE [System Units] (
  [Asset Code] NVARCHAR(MAX) NULL,
  [Asset Category] NVARCHAR(MAX) NULL,
  [Reporting Branch] NVARCHAR(MAX) NULL,
  [Office] NVARCHAR(MAX) NULL,
  [Assigned Main Asset No.] NVARCHAR(MAX) NULL,
  [Current User] NVARCHAR(MAX) NULL,
  [Asset Status] NVARCHAR(MAX) NULL,
  [Brand] NVARCHAR(MAX) NULL,
  [Model] NVARCHAR(MAX) NULL,
  [Serial Number] NVARCHAR(MAX) NULL,
  [Processor] NVARCHAR(MAX) NULL,
  [RAM] NVARCHAR(MAX) NULL,
  [Storage] NVARCHAR(MAX) NULL,
  [Operating System] NVARCHAR(MAX) NULL,
  [PC Name] NVARCHAR(MAX) NULL,
  [MAC Address] NVARCHAR(MAX) NULL,
  [IP Address] NVARCHAR(MAX) NULL,
  [Date Deployed] NVARCHAR(MAX) NULL,
  [Purchase Date] NVARCHAR(MAX) NULL,
  [Purchase Price] NVARCHAR(MAX) NULL,
  [Expected Life (Years)] NVARCHAR(MAX) NULL,
  [Residual Value] NVARCHAR(MAX) NULL,
  [Current Value] NVARCHAR(MAX) NULL,
  [Device EOL] NVARCHAR(MAX) NULL,
  [Fully Depreciated Date] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Created At] NVARCHAR(MAX) NULL,
  [Updated At] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'Laptops', N'U') IS NOT NULL DROP TABLE [Laptops];
GO

CREATE TABLE [Laptops] (
  [Asset Code] NVARCHAR(MAX) NULL,
  [Asset Category] NVARCHAR(MAX) NULL,
  [Reporting Branch] NVARCHAR(MAX) NULL,
  [Office] NVARCHAR(MAX) NULL,
  [Assigned Main Asset No.] NVARCHAR(MAX) NULL,
  [Current User] NVARCHAR(MAX) NULL,
  [Asset Status] NVARCHAR(MAX) NULL,
  [Brand] NVARCHAR(MAX) NULL,
  [Model] NVARCHAR(MAX) NULL,
  [Serial Number] NVARCHAR(MAX) NULL,
  [Processor] NVARCHAR(MAX) NULL,
  [RAM] NVARCHAR(MAX) NULL,
  [Storage] NVARCHAR(MAX) NULL,
  [Operating System] NVARCHAR(MAX) NULL,
  [Laptop Name] NVARCHAR(MAX) NULL,
  [MAC Address] NVARCHAR(MAX) NULL,
  [Date Deployed] NVARCHAR(MAX) NULL,
  [Purchase Date] NVARCHAR(MAX) NULL,
  [Purchase Price] NVARCHAR(MAX) NULL,
  [Expected Life (Years)] NVARCHAR(MAX) NULL,
  [Residual Value] NVARCHAR(MAX) NULL,
  [Current Value] NVARCHAR(MAX) NULL,
  [Device EOL] NVARCHAR(MAX) NULL,
  [Fully Depreciated Date] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Created At] NVARCHAR(MAX) NULL,
  [Updated At] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'Monitors', N'U') IS NOT NULL DROP TABLE [Monitors];
GO

CREATE TABLE [Monitors] (
  [Asset Code] NVARCHAR(MAX) NULL,
  [Asset Category] NVARCHAR(MAX) NULL,
  [Reporting Branch] NVARCHAR(MAX) NULL,
  [Office] NVARCHAR(MAX) NULL,
  [Assigned Main Asset No.] NVARCHAR(MAX) NULL,
  [Current User] NVARCHAR(MAX) NULL,
  [Asset Status] NVARCHAR(MAX) NULL,
  [Brand] NVARCHAR(MAX) NULL,
  [Model] NVARCHAR(MAX) NULL,
  [Serial Number] NVARCHAR(MAX) NULL,
  [Date Deployed] NVARCHAR(MAX) NULL,
  [Purchase Date] NVARCHAR(MAX) NULL,
  [Purchase Price] NVARCHAR(MAX) NULL,
  [Expected Life (Years)] NVARCHAR(MAX) NULL,
  [Residual Value] NVARCHAR(MAX) NULL,
  [Current Value] NVARCHAR(MAX) NULL,
  [Device EOL] NVARCHAR(MAX) NULL,
  [Fully Depreciated Date] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Created At] NVARCHAR(MAX) NULL,
  [Updated At] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'Mouse', N'U') IS NOT NULL DROP TABLE [Mouse];
GO

CREATE TABLE [Mouse] (
  [Asset Code] NVARCHAR(MAX) NULL,
  [Asset Category] NVARCHAR(MAX) NULL,
  [Reporting Branch] NVARCHAR(MAX) NULL,
  [Office] NVARCHAR(MAX) NULL,
  [Assigned Main Asset No.] NVARCHAR(MAX) NULL,
  [Current User] NVARCHAR(MAX) NULL,
  [Asset Status] NVARCHAR(MAX) NULL,
  [Brand] NVARCHAR(MAX) NULL,
  [Model] NVARCHAR(MAX) NULL,
  [Serial Number] NVARCHAR(MAX) NULL,
  [Date Deployed] NVARCHAR(MAX) NULL,
  [Purchase Date] NVARCHAR(MAX) NULL,
  [Purchase Price] NVARCHAR(MAX) NULL,
  [Expected Life (Years)] NVARCHAR(MAX) NULL,
  [Residual Value] NVARCHAR(MAX) NULL,
  [Current Value] NVARCHAR(MAX) NULL,
  [Device EOL] NVARCHAR(MAX) NULL,
  [Fully Depreciated Date] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Created At] NVARCHAR(MAX) NULL,
  [Updated At] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'Keyboards', N'U') IS NOT NULL DROP TABLE [Keyboards];
GO

CREATE TABLE [Keyboards] (
  [Asset Code] NVARCHAR(MAX) NULL,
  [Asset Category] NVARCHAR(MAX) NULL,
  [Reporting Branch] NVARCHAR(MAX) NULL,
  [Office] NVARCHAR(MAX) NULL,
  [Assigned Main Asset No.] NVARCHAR(MAX) NULL,
  [Current User] NVARCHAR(MAX) NULL,
  [Asset Status] NVARCHAR(MAX) NULL,
  [Brand] NVARCHAR(MAX) NULL,
  [Model] NVARCHAR(MAX) NULL,
  [Serial Number] NVARCHAR(MAX) NULL,
  [Date Deployed] NVARCHAR(MAX) NULL,
  [Purchase Date] NVARCHAR(MAX) NULL,
  [Purchase Price] NVARCHAR(MAX) NULL,
  [Expected Life (Years)] NVARCHAR(MAX) NULL,
  [Residual Value] NVARCHAR(MAX) NULL,
  [Current Value] NVARCHAR(MAX) NULL,
  [Device EOL] NVARCHAR(MAX) NULL,
  [Fully Depreciated Date] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Created At] NVARCHAR(MAX) NULL,
  [Updated At] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'UPS', N'U') IS NOT NULL DROP TABLE [UPS];
GO

CREATE TABLE [UPS] (
  [Asset Code] NVARCHAR(MAX) NULL,
  [Asset Category] NVARCHAR(MAX) NULL,
  [Reporting Branch] NVARCHAR(MAX) NULL,
  [Office] NVARCHAR(MAX) NULL,
  [Assigned Main Asset No.] NVARCHAR(MAX) NULL,
  [Current User] NVARCHAR(MAX) NULL,
  [Asset Status] NVARCHAR(MAX) NULL,
  [Brand] NVARCHAR(MAX) NULL,
  [Model] NVARCHAR(MAX) NULL,
  [Serial Number] NVARCHAR(MAX) NULL,
  [Capacity (VA)] NVARCHAR(MAX) NULL,
  [Date Deployed] NVARCHAR(MAX) NULL,
  [Purchase Date] NVARCHAR(MAX) NULL,
  [Purchase Price] NVARCHAR(MAX) NULL,
  [Expected Life (Years)] NVARCHAR(MAX) NULL,
  [Residual Value] NVARCHAR(MAX) NULL,
  [Current Value] NVARCHAR(MAX) NULL,
  [Device EOL] NVARCHAR(MAX) NULL,
  [Fully Depreciated Date] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Created At] NVARCHAR(MAX) NULL,
  [Updated At] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'Printers', N'U') IS NOT NULL DROP TABLE [Printers];
GO

CREATE TABLE [Printers] (
  [Asset Code] NVARCHAR(MAX) NULL,
  [Asset Category] NVARCHAR(MAX) NULL,
  [Reporting Branch] NVARCHAR(MAX) NULL,
  [Office] NVARCHAR(MAX) NULL,
  [Assigned Main Asset No.] NVARCHAR(MAX) NULL,
  [Current User] NVARCHAR(MAX) NULL,
  [Asset Status] NVARCHAR(MAX) NULL,
  [Brand] NVARCHAR(MAX) NULL,
  [Model] NVARCHAR(MAX) NULL,
  [Serial Number] NVARCHAR(MAX) NULL,
  [Printer Type] NVARCHAR(MAX) NULL,
  [Date Deployed] NVARCHAR(MAX) NULL,
  [Purchase Date] NVARCHAR(MAX) NULL,
  [Purchase Price] NVARCHAR(MAX) NULL,
  [Expected Life (Years)] NVARCHAR(MAX) NULL,
  [Residual Value] NVARCHAR(MAX) NULL,
  [Current Value] NVARCHAR(MAX) NULL,
  [Device EOL] NVARCHAR(MAX) NULL,
  [Fully Depreciated Date] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Created At] NVARCHAR(MAX) NULL,
  [Updated At] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'Asset_History', N'U') IS NOT NULL DROP TABLE [Asset_History];
GO

CREATE TABLE [Asset_History] (
  [History ID] NVARCHAR(MAX) NULL,
  [Transaction Date] NVARCHAR(MAX) NULL,
  [Action] NVARCHAR(MAX) NULL,
  [Asset Code] NVARCHAR(MAX) NULL,
  [Main Asset Number] NVARCHAR(MAX) NULL,
  [From User] NVARCHAR(MAX) NULL,
  [To User] NVARCHAR(MAX) NULL,
  [Reporting Branch] NVARCHAR(MAX) NULL,
  [Office] NVARCHAR(MAX) NULL,
  [Status After] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO

IF OBJECT_ID(N'Repair_Log', N'U') IS NOT NULL DROP TABLE [Repair_Log];
GO

CREATE TABLE [Repair_Log] (
  [Repair ID] NVARCHAR(MAX) NULL,
  [Date Reported] NVARCHAR(MAX) NULL,
  [Asset Code] NVARCHAR(MAX) NULL,
  [Asset Category] NVARCHAR(MAX) NULL,
  [Issue] NVARCHAR(MAX) NULL,
  [Sent To] NVARCHAR(MAX) NULL,
  [Date Sent] NVARCHAR(MAX) NULL,
  [Date Returned] NVARCHAR(MAX) NULL,
  [Repair Status] NVARCHAR(MAX) NULL,
  [Cost] NVARCHAR(MAX) NULL,
  [Remarks] NVARCHAR(MAX) NULL,
  [Created At] NVARCHAR(MAX) NULL,
  [Updated At] NVARCHAR(MAX) NULL,
  [Encoded By] NVARCHAR(MAX) NULL
);
GO
