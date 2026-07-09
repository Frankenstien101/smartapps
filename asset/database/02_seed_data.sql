USE [BSPI_Asset_Manager];
GO

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (N'ALL SITES', N'Yes', N'WDC', N'System Unit', N'SU', N'5', N'Ready to Deploy', N'Deployed', N'Yes');

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (N'DVO', N'Yes', N'NEBRASKA', N'Monitor', N'MON', N'5', N'Deployed', N'Ready to Deploy', N'No');

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (N'KOR', N'Yes', N'SILVERSWAN', N'Mouse', N'MOU', N'3', N'Broken - In Repair', N'Incomplete Kit', NULL);

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (N'CDO', N'Yes', N'DELMONTE', N'Keyboard', N'KB', N'3', N'Disposed', N'Pulled Out', NULL);

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (N'BXU', N'Yes', N'FDI-PRAN-GEN', N'UPS', N'UPS', N'5', N'In Transit', N'Archived', NULL);

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (N'ZAM', N'Yes', N'EMPERADOR', N'Printer', N'PRT', N'5', N'Returned', NULL, NULL);

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (N'OZA', N'Yes', N'ULQ', N'Laptop', N'LAP', N'5', N'Lost', NULL, NULL);

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (NULL, NULL, N'KFI', NULL, NULL, NULL, NULL, NULL, NULL);

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (NULL, NULL, N'FINANCE', NULL, NULL, NULL, NULL, NULL, NULL);

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (NULL, NULL, N'HR', NULL, NULL, NULL, NULL, NULL, NULL);

INSERT INTO [Settings] ([SITES], [ACTIVE], [DEPARTMENTS / OFFICES], [ASSET CATEGORY], [PREFIX], [DEFAULT EOL (YEARS)], [ASSET STATUSES], [DEPLOYMENT STATUSES], [ACTIVE FLAGS]) VALUES (NULL, NULL, N'LOGISTICS', NULL, NULL, NULL, NULL, NULL, NULL);

GO

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (N'DVO', N'0', NULL, NULL, NULL);

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (N'KOR', N'0', NULL, NULL, NULL);

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (N'CDO', N'0', NULL, NULL, NULL);

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (N'BXU', N'0', NULL, NULL, NULL);

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (N'ZAM', N'0', NULL, NULL, NULL);

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (N'OZA', N'0', NULL, NULL, NULL);

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (NULL, NULL, N'System Unit', N'SU', N'3');

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (NULL, NULL, N'Monitor', N'MON', N'0');

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (NULL, NULL, N'Mouse', N'MOU', N'0');

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (NULL, NULL, N'Keyboard', N'KB', N'0');

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (NULL, NULL, N'UPS', N'UPS', N'0');

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (NULL, NULL, N'Printer', N'PRT', N'0');

INSERT INTO [Asset_Counters] ([SITE], [LAST STATION NO USED], [ASSET CATEGORY], [PREFIX], [LAST ASSET NO USED]) VALUES (NULL, NULL, N'Laptop', N'LAP', N'0');

GO

INSERT INTO [System Units] ([Asset Code], [Asset Category], [Reporting Branch], [Office], [Assigned Main Asset No.], [Current User], [Asset Status], [Brand], [Model], [Serial Number], [Processor], [RAM], [Storage], [Operating System], [PC Name], [MAC Address], [IP Address], [Date Deployed], [Purchase Date], [Purchase Price], [Expected Life (Years)], [Residual Value], [Current Value], [Device EOL], [Fully Depreciated Date], [Remarks], [Created At], [Updated At], [Encoded By]) VALUES (N'SU-0001', N'System Unit', N'DVO', N'WDC', NULL, NULL, N'Ready to Deploy', N'Acer', N'Aspire TC-1775', N'SN-1776675892806', N'Intel Core i5', N'16GB', N'512GB SSD', N'Windows 11 Pro', N'BSPI-PC-01', N'00:11:22:33:44:55', N'192.168.1.10', NULL, N'2026-04-20 02:04:52', N'53000', N'5', N'5000', N'53000', NULL, NULL, N'Created from test helper.', N'2026-04-20 02:04:57', N'2026-04-20 02:04:57', N'Admin');

INSERT INTO [System Units] ([Asset Code], [Asset Category], [Reporting Branch], [Office], [Assigned Main Asset No.], [Current User], [Asset Status], [Brand], [Model], [Serial Number], [Processor], [RAM], [Storage], [Operating System], [PC Name], [MAC Address], [IP Address], [Date Deployed], [Purchase Date], [Purchase Price], [Expected Life (Years)], [Residual Value], [Current Value], [Device EOL], [Fully Depreciated Date], [Remarks], [Created At], [Updated At], [Encoded By]) VALUES (N'SU-0002', N'System Unit', N'DVO', N'WDC', NULL, NULL, N'Ready to Deploy', N'Acer', N'Aspire TC-1775', N'SN-1776682117249', N'Intel Core i5', N'16GB', N'512GB SSD', N'Windows 11 Pro', N'BSPI-PC-01', N'00:11:22:33:44:55', N'192.168.1.10', NULL, N'2026-04-20 03:48:37', N'53000', N'5', N'5000', N'53000', NULL, NULL, N'Created from test helper.', N'2026-04-20 03:48:39', N'2026-04-20 03:48:39', N'Admin');

INSERT INTO [System Units] ([Asset Code], [Asset Category], [Reporting Branch], [Office], [Assigned Main Asset No.], [Current User], [Asset Status], [Brand], [Model], [Serial Number], [Processor], [RAM], [Storage], [Operating System], [PC Name], [MAC Address], [IP Address], [Date Deployed], [Purchase Date], [Purchase Price], [Expected Life (Years)], [Residual Value], [Current Value], [Device EOL], [Fully Depreciated Date], [Remarks], [Created At], [Updated At], [Encoded By]) VALUES (N'SU-0003', N'System Unit', N'DVO', N'WDC', NULL, N'FACIOL, ANTONETTE', N'Deployed', N'ACER', N'Aspire-TC-1775', N'DTBLQSP001439006A39600', N'Intel Core i3-14100', N'8GB', N'256SSD/1TB', N'WINDOWS 11 HOME', N'WDC-01', N'88-AE-DD-8B-24-DC', N'172.40.0.101', NULL, N'2025-04-08 09:00:00', N'42998.98', N'5', N'4299.9', N'35009.74', N'2030-04-08 09:00:00', N'2030-04-08 09:00:00', NULL, N'2026-04-20 08:15:06', N'2026-04-20 08:15:06', N'Admin');

GO

INSERT INTO [Asset_History] ([History ID], [Transaction Date], [Action], [Asset Code], [Main Asset Number], [From User], [To User], [Reporting Branch], [Office], [Status After], [Remarks], [Encoded By]) VALUES (N'HIST-E8C01534', N'2026-04-20 02:04:57', N'ASSET CREATED', N'SU-0001', NULL, NULL, NULL, N'DVO', N'WDC', N'Ready to Deploy', N'Created from test helper.', N'Admin');

INSERT INTO [Asset_History] ([History ID], [Transaction Date], [Action], [Asset Code], [Main Asset Number], [From User], [To User], [Reporting Branch], [Office], [Status After], [Remarks], [Encoded By]) VALUES (N'HIST-2F18101C', N'2026-04-20 03:48:39', N'ASSET CREATED', N'SU-0002', NULL, NULL, NULL, N'DVO', N'WDC', N'Ready to Deploy', N'Created from test helper.', N'Admin');

INSERT INTO [Asset_History] ([History ID], [Transaction Date], [Action], [Asset Code], [Main Asset Number], [From User], [To User], [Reporting Branch], [Office], [Status After], [Remarks], [Encoded By]) VALUES (N'HIST-4F873231', N'2026-04-20 08:15:06', N'ASSET CREATED', N'SU-0003', NULL, NULL, N'FACIOL, ANTONETTE', N'DVO', N'WDC', N'Deployed', N'Asset created via app.', N'Admin');

GO
