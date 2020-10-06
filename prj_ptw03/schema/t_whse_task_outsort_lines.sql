USE [UPIAPP]
GO

SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[t_whse_task_outsort_lines](
	[task_line_id] [uniqueidentifier] NOT NULL,
	[request_id] [int] NULL,
	[request_line_id] [int] NULL,
	[wh_id] [int] NULL,
	[wh_code] [nvarchar](30) NULL,
	[client_id] [int] NULL,
	[client_code] [nvarchar](30) NULL,
	[status] [nvarchar](1) NOT NULL,
	[pick_wave_id] [int] NULL,
	[wave_number] [nvarchar](30) NULL,
	[wave_order_id] [int] NULL,
	[wave_order_line_id] [int] NULL,
	[load_id] [int] NULL,
	[load_number] [nvarchar](30) NULL,
	[delivery_id] [int] NULL,
	[shipment_number] [nvarchar](30) NULL,
	[carrier_id] [int] NULL,
	[carrier_code] [nvarchar](30) NULL,
	[service_id] [int] NULL,
	[service_code] [nvarchar](30) NULL,
	[route_id] [int] NULL,
	[route_code] [nvarchar](30) NULL,
	[customer_code] [nvarchar](100) NULL,
	[customer_name] [nvarchar](255) NULL,
	[ship_to_code] [nvarchar](100) NULL,
	[ship_to_name] [nvarchar](255) NULL,
	[ship_to_zip] [nvarchar](50) NULL,
	[outsort_sequence] [int] NULL,
	[delivery_sequence] [int] NULL,
	[stock_by_uom] [tinyint] NULL,
	[order_id] [int] NULL,
	[order_number] [nvarchar](30) NULL,
	[order_date] [datetime] NULL,
	[line_id] [int] NULL,
	[line_number] [nvarchar](30) NULL,
	[payment_term] [nvarchar](30) NULL,
	[from_locator_id] [int] NULL,
	[from_locator_code] [nvarchar](30) NULL,
	[from_locator_pickflow_seq] [nvarchar](10) NULL,
	[from_locator_checkdigit] [nvarchar](10) NULL,
	[from_pick_zone_id] [int] NULL,
	[from_pick_zone] [nvarchar](30) NULL,
	[to_locator_id] [int] NULL,
	[to_locator_code] [nvarchar](30) NULL,
	[to_locator_putflow_seq] [nvarchar](10) NULL,
	[to_locator_checkdigit] [nvarchar](10) NULL,
	[item_id] [int] NULL,
	[item_number] [nvarchar](30) NULL,
	[alt_item_number] [nvarchar](30) NULL,
	[item_description] [nvarchar](255) NULL,
	[item_display_ext] [nvarchar](50) NULL,
	[barcoded_flag] [tinyint] NULL,
	[item_barcode_1] [nvarchar](30) NULL,
	[item_barcode_2] [nvarchar](30) NULL,
	[item_barcode_3] [nvarchar](30) NULL,
	[item_barcode_4] [nvarchar](30) NULL,
	[item_barcode_5] [nvarchar](30) NULL,
	[item_image_url] [nvarchar](255) NULL,
	[item_type_id] [int] NULL,
	[item_type_code] [nvarchar](30) NULL,
	[item_type_name] [nvarchar](100) NULL,
	[item_class_id] [int] NULL,
	[item_class_code] [nvarchar](30) NULL,
	[item_class_name] [nvarchar](100) NULL,
	[item_category_id] [int] NULL,
	[item_category_code] [nvarchar](30) NULL,
	[item_category_name] [nvarchar](100) NULL,
	[stock_attribute_id] [int] NULL,
	[stock_attribute_val] [nvarchar](1000) NULL,
	[stock_attribute_1] [nvarchar](255) NULL,
	[stock_attribute_2] [nvarchar](255) NULL,
	[stock_attribute_3] [nvarchar](255) NULL,
	[stock_attribute_4] [nvarchar](255) NULL,
	[stock_attribute_5] [nvarchar](255) NULL,
	[stock_attribute_6] [nvarchar](255) NULL,
	[stock_attribute_7] [nvarchar](255) NULL,
	[stock_attribute_8] [nvarchar](255) NULL,
	[stock_attribute_9] [nvarchar](255) NULL,
	[stock_attribute_10] [nvarchar](255) NULL,
	[lpn_master_id] [int] NULL,
	[lpn_number] [nvarchar](30) NULL,
	[lpn_checkdigit] [nvarchar](10) NULL,
	[lpn_checkdigit_disp] [nvarchar](10) NULL,
	[to_lpn_master_id] [int] NULL,
	[to_lpn_number] [nvarchar](30) NULL,
	[to_lpn_checkdigit] [nvarchar](10) NULL,
	[to_lpn_checkdigit_disp] [nvarchar](10) NULL,
	[to_lpn_type] [nvarchar](10) NULL,
	[to_lpn_cartonize_flag] [tinyint] NULL,
	[to_lpn_doc_id] [int] NULL,
	[lot_number] [nvarchar](30) NULL,
	[revision_number] [nvarchar](30) NULL,
	[expiration_date] [datetime] NULL,
	[shelf_life] [int] NULL,
	[condition_id] [int] NULL,
	[condition_code] [nvarchar](30) NULL,
	[condition_name] [nvarchar](30) NULL,
	[plan_uom_id] [int] NULL,
	[plan_uom] [nvarchar](30) NULL,
	[plan_pack_type_id] [int] NULL,
	[plan_pack_type] [nvarchar](30) NULL,
	[uom_id] [int] NULL,
	[uom] [nvarchar](30) NULL,
	[uom_conversion_ratio] [float] NULL,
	[uom_display] [nvarchar](50) NULL,
	[to_uom_id] [int] NULL,
	[to_uom] [nvarchar](30) NULL,
	[to_uom_conversion_ratio] [float] NULL,
	[to_uom_display] [nvarchar](50) NULL,
	[to_pack_type_id] [int] NULL,
	[to_pack_type] [nvarchar](30) NULL,
	[uom_flag] [tinyint] NULL,
	[plan_base_qty] [float] NOT NULL,
	[completed_based_qty] [float] NOT NULL,
	[canceled_base_qty] [float] NOT NULL,
	[has_attribute_flag] [tinyint] NULL,
	[priority] [int] NOT NULL,
	[work_on_user_id] [int] NULL,
	[work_on_username] [nvarchar](30) NULL,
	[assign_to_user_id] [int] NULL,
	[assign_to_username] [nvarchar](30) NULL,
	[picking_instruction] [nvarchar](255) NULL,
	[packing_instruction] [nvarchar](255) NULL,
	[shipping_instruction] [nvarchar](255) NULL,
	[ref_id_num_1] [bigint] NULL,
	[ref_id_num_2] [bigint] NULL,
	[ref_id_num_3] [bigint] NULL,
	[ref_id_num_4] [bigint] NULL,
	[ref_id_num_5] [bigint] NULL,
	[ref_id_txt_1] [nvarchar](50) NULL,
	[ref_id_txt_2] [nvarchar](50) NULL,
	[ref_id_txt_3] [nvarchar](50) NULL,
	[ref_id_txt_4] [nvarchar](50) NULL,
	[ref_id_txt_5] [nvarchar](50) NULL,
	[create_datetime] [datetime] NULL,
	[create_by] [int] NULL,
	[last_update_datetime] [datetime] NULL,
	[last_update_by] [int] NULL,
	[version] [int] NULL,
	[attribute_context] [nvarchar](30) NULL,
	[attribute1] [nvarchar](255) NULL,
	[attribute2] [nvarchar](255) NULL,
	[attribute3] [nvarchar](255) NULL,
	[attribute4] [nvarchar](255) NULL,
	[attribute5] [nvarchar](255) NULL,
	[attribute6] [nvarchar](255) NULL,
	[attribute7] [nvarchar](255) NULL,
	[attribute8] [nvarchar](255) NULL,
	[attribute9] [nvarchar](255) NULL,
	[attribute10] [nvarchar](255) NULL,
	[attribute11] [nvarchar](255) NULL,
	[attribute12] [nvarchar](255) NULL,
	[attribute13] [nvarchar](255) NULL,
	[attribute14] [nvarchar](255) NULL,
	[attribute15] [nvarchar](255) NULL,
	[attribute16] [nvarchar](255) NULL,
	[attribute17] [nvarchar](255) NULL,
	[attribute18] [nvarchar](255) NULL,
	[attribute19] [nvarchar](255) NULL,
	[attribute20] [nvarchar](255) NULL,
 CONSTRAINT [PK_t_whse_task_outsort_lines] PRIMARY KEY CLUSTERED 
(
	[task_line_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = OFF, FILLFACTOR = 80) ON [PRIMARY]
) ON [PRIMARY]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_task_line_id]  DEFAULT (newsequentialid()) FOR [task_line_id]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_Table_1_status1]  DEFAULT (N'O') FOR [status]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_uom_flag]  DEFAULT ((0)) FOR [uom_flag]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_completed_based_qty]  DEFAULT ((0)) FOR [completed_based_qty]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_canceled_base_qty]  DEFAULT ((0)) FOR [canceled_base_qty]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_has_attribute_flag]  DEFAULT ((0)) FOR [has_attribute_flag]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_priority]  DEFAULT ((0)) FOR [priority]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_create_datetime]  DEFAULT (getdate()) FOR [create_datetime]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_create_by]  DEFAULT (NULL) FOR [create_by]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_last_update_datetime]  DEFAULT (NULL) FOR [last_update_datetime]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_last_update_by]  DEFAULT (NULL) FOR [last_update_by]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_version]  DEFAULT ((1)) FOR [version]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute_context]  DEFAULT (NULL) FOR [attribute_context]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute1]  DEFAULT (NULL) FOR [attribute1]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute2]  DEFAULT (NULL) FOR [attribute2]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute3]  DEFAULT (NULL) FOR [attribute3]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute4]  DEFAULT (NULL) FOR [attribute4]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute5]  DEFAULT (NULL) FOR [attribute5]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute6]  DEFAULT (NULL) FOR [attribute6]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute7]  DEFAULT (NULL) FOR [attribute7]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute8]  DEFAULT (NULL) FOR [attribute8]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute9]  DEFAULT (NULL) FOR [attribute9]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute10]  DEFAULT (NULL) FOR [attribute10]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute11]  DEFAULT (NULL) FOR [attribute11]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute12]  DEFAULT (NULL) FOR [attribute12]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute13]  DEFAULT (NULL) FOR [attribute13]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute14]  DEFAULT (NULL) FOR [attribute14]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute15]  DEFAULT (NULL) FOR [attribute15]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute16]  DEFAULT (NULL) FOR [attribute16]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute17]  DEFAULT (NULL) FOR [attribute17]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute18]  DEFAULT (NULL) FOR [attribute18]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute19]  DEFAULT (NULL) FOR [attribute19]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] ADD  CONSTRAINT [DF_t_whse_task_outsort_lines_attribute20]  DEFAULT (NULL) FOR [attribute20]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines]  WITH CHECK ADD  CONSTRAINT [fk_tskoutsrtln_clt] FOREIGN KEY([client_id])
REFERENCES [dbo].[t_app_entities] ([entity_id])
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] CHECK CONSTRAINT [fk_tskoutsrtln_clt]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines]  WITH CHECK ADD  CONSTRAINT [fk_tskoutsrtln_itm] FOREIGN KEY([item_id])
REFERENCES [dbo].[t_item_masters] ([item_id])
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] CHECK CONSTRAINT [fk_tskoutsrtln_itm]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines]  WITH CHECK ADD  CONSTRAINT [fk_tskoutsrtln_orh] FOREIGN KEY([order_id])
REFERENCES [dbo].[t_ob_order_headers] ([order_id])
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] CHECK CONSTRAINT [fk_tskoutsrtln_orh]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines]  WITH CHECK ADD  CONSTRAINT [fk_tskoutsrtln_pkw] FOREIGN KEY([pick_wave_id])
REFERENCES [dbo].[t_whse_pick_waves] ([pick_wave_id])
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] CHECK CONSTRAINT [fk_tskoutsrtln_pkw]
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines]  WITH CHECK ADD  CONSTRAINT [fk_tskoutsrtln_wh] FOREIGN KEY([wh_id])
REFERENCES [dbo].[t_app_entities] ([entity_id])
GO

ALTER TABLE [dbo].[t_whse_task_outsort_lines] CHECK CONSTRAINT [fk_tskoutsrtln_wh]
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Primary Key' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'task_line_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Request ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'request_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Request Line ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'request_line_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Warehouse ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'wh_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Warehouse Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'wh_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Client ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'client_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Client Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'client_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Status, ref lookup code WMS_PICK_REQUEST_STATUS' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'status'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Pick Wave ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'pick_wave_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Wave Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'wave_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Wave Order ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'wave_order_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Wave Order Line ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'wave_order_line_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Load ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'load_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Load Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'load_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Delivery ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'delivery_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Shipment Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'shipment_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Carrier ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'carrier_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Carrier Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'carrier_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Carrier Service ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'service_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Carrier Service Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'service_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Route ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'route_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Route Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'route_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Customer Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'customer_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Customer Name' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'customer_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Ship To Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ship_to_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Ship To Name' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ship_to_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Ship To Postal Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ship_to_zip'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Outsort Sequence' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'outsort_sequence'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Delivery Sequence' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'delivery_sequence'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Stock By UoM Flag' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_by_uom'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Order ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'order_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Order Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'order_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Order Date' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'order_date'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Order Line ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'line_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Order Line Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'line_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Payment Term' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'payment_term'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'From Locator ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'from_locator_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'From Locator Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'from_locator_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'From Locator Pick Flow Sequence' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'from_locator_pickflow_seq'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'From Locator Check Digit' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'from_locator_checkdigit'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'From Pick Zone ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'from_pick_zone_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'From Pick Zone' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'from_pick_zone'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To Locator ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_locator_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To Locator Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_locator_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To Locator Putaway Flow Sequence' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_locator_putflow_seq'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To Locator Check Digit' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_locator_checkdigit'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Alternative Item Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'alt_item_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Description' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_description'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Display Extension' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_display_ext'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Barcoded Flag' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'barcoded_flag'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Barcode 1' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_barcode_1'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Barcode 2' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_barcode_2'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Barcode 3' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_barcode_3'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Barcode 4' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_barcode_4'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Barcode 5' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_barcode_5'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Image URL' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_image_url'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Type ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_type_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Type Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_type_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Type Name' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_type_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Class ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_class_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Class Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_class_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Class Name' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_class_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Category ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_category_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Category Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_category_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Category Name' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'item_category_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute Value' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_val'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 1' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_1'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 2' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_2'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 3' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_3'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 4' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_4'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 5' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_5'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 6' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_6'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 7' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_7'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 8' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_8'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 9' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_9'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Attribute 10' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'stock_attribute_10'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'LPN Master ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'lpn_master_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'LPN Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'lpn_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'LPN Check Digit' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'lpn_checkdigit'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'LPN Check Digit Display' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'lpn_checkdigit_disp'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To LPN Master ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_lpn_master_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To LPN Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_lpn_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To LPN Check Digit' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_lpn_checkdigit'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To LPN Check Digit Display' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_lpn_checkdigit_disp'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To LPN Type' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_lpn_type'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To LPN Cartonize Flag' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_lpn_cartonize_flag'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To LPN Document ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_lpn_doc_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Lot Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'lot_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Revision Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'revision_number'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Expiry Date' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'expiration_date'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Shelf Life' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'shelf_life'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Item Condition ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'condition_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Condition Code' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'condition_code'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Condition Name' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'condition_name'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Plan UoM ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'plan_uom_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Plan UoM' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'plan_uom'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Plan Pack Type ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'plan_pack_type_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Plan Pack Type' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'plan_pack_type'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'UoM ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'uom_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'UoM' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'uom'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'UoM Conversion Ratio' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'uom_conversion_ratio'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'UoM Display' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'uom_display'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To UoM ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_uom_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To UoM' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_uom'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To UoM Conversion Ratio' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_uom_conversion_ratio'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To UoM Display' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_uom_display'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To Pack Type ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_pack_type_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'To Pack Type' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'to_pack_type'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Flag to indicate enforce UoM' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'uom_flag'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Request Base Quantity' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'plan_base_qty'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Completed Base Qty' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'completed_based_qty'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Canceled Base Quantity' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'canceled_base_qty'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Has Attribute Flag' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'has_attribute_flag'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Priority' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'priority'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Work On User ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'work_on_user_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Work On Username' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'work_on_username'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Assign To User ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'assign_to_user_id'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Assign To Username' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'assign_to_username'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Picking Instruction' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'picking_instruction'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Packing Instruction' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'packing_instruction'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Shipping Instruction' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'shipping_instruction'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Number 1' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_num_1'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Number 2' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_num_2'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Number 3' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_num_3'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Number 4' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_num_4'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Number 5' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_num_5'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Text 1' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_txt_1'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Text 2' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_txt_2'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Text 3' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_txt_3'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Text 4' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_txt_4'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Reference ID Text 5' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'ref_id_txt_5'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Record Create Timestamp' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'create_datetime'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Record Created by User ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'create_by'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Record Last Update Timestamp' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'last_update_datetime'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Record Last Update by User ID' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'last_update_by'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Record Version Number' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'version'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field Context' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute_context'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 1' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute1'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 2' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute2'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 3' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute3'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 4' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute4'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 5' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute5'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 6' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute6'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 7' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute7'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 8' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute8'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 9' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute9'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 10' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute10'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 11' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute11'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 12' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute12'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 13' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute13'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 14' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute14'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 15' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute15'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 16' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute16'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 17' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute17'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 18' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute18'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 19' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute19'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Custom Field 20' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines', @level2type=N'COLUMN',@level2name=N'attribute20'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'This table stores warehouse pick outsort task records' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines'
GO

EXEC sys.sp_addextendedproperty @name=N'UPI_ModuleCode', @value=N'WMS' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines'
GO

EXEC sys.sp_addextendedproperty @name=N'UPI_Version', @value=N'2017-04-16' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N't_whse_task_outsort_lines'
GO

