<?php $this->load->view("partial/header"); ?>
<div id="page_title" style="margin-bottom:8px;"><?php echo $this->lang->line('reports_reports'); ?></div>
<div id="welcome_message"><?php echo $this->lang->line('reports_welcome_message'); ?>
<ul id="report_list">
	<li>Graphical Reports
		<ul>
			<li><a href="<?php echo site_url('reports/graphical_summary_sales');?>"><?php echo $this->lang->line('reports_sales'); ?></a></li>
			<li><a href="<?php echo site_url('reports/graphical_summary_categories');?>"><?php echo $this->lang->line('reports_categories'); ?></a></li>
			<li><a href="<?php echo site_url('reports/graphical_summary_customers');?>"><?php echo $this->lang->line('reports_customers'); ?></a></li>
			<li><a href="<?php echo site_url('reports/graphical_summary_suppliers');?>"><?php echo $this->lang->line('reports_suppliers'); ?></a></li>
			<li><a href="<?php echo site_url('reports/graphical_summary_items');?>"><?php echo $this->lang->line('reports_items'); ?></a></li>
			<li><a href="<?php echo site_url('reports/graphical_summary_employees');?>"><?php echo $this->lang->line('reports_employees'); ?></a></li>
			<li><a href="<?php echo site_url('reports/graphical_summary_taxes');?>"><?php echo $this->lang->line('reports_taxes'); ?></a></li>
		</ul>
	</li>
	<li>Accounting Reports
		<ul>
			<li><a href="application/views/reports/sales.php"><?php echo "Sales"; ?></a></li>
			<li><a href="application/views/reports/rentsales.php"><?php echo "Rent"; ?></a></li>
			<li><a href="application/views/reports/purchase.php"><?php echo "Purchase"; ?></a></a></li>
			<li><a href="application/views/reports/stockbal.php"><?php echo "Stock"; ?></a></li>
			<li><a href="application/views/reports/stockbal_approval.php"><?php echo "Stock bailance with Approval"; ?></a></li>
			<li><a href="application/views/reports/customerbal.php"><?php echo "Customer Balances"; ?></a></li>
			<li><a href="application/views/reports/vender_outstanding.php"><?php echo "Vendor Outstanding"; ?></a></li>			
		</ul>
	</li>
	<li>Summary Reports
		<ul>
			<li><a href="<?php echo site_url('reports/summary_sales');?>"><?php echo $this->lang->line('reports_sales'); ?></a></li>
			<li><a href="<?php echo site_url('reports/summary_categories');?>"><?php echo $this->lang->line('reports_categories'); ?></a></li>
			<li><a href="<?php echo site_url('reports/summary_customers');?>"><?php echo $this->lang->line('reports_customers'); ?></a></li>
			<li><a href="<?php echo site_url('reports/summary_suppliers');?>"><?php echo $this->lang->line('reports_suppliers'); ?></a></li>
			<li><a href="<?php echo site_url('reports/summary_items');?>"><?php echo $this->lang->line('reports_items'); ?></a></li>
			<li><a href="<?php echo site_url('reports/summary_employees');?>"><?php echo $this->lang->line('reports_employees'); ?></a></li>
			<li><a href="<?php echo site_url('reports/summary_taxes');?>"><?php echo $this->lang->line('reports_taxes'); ?></a></li>
			
			
			<li><a href="application/views/reports/approval.php"><?php echo "Approval"; ?></a></li>
			<li><a href="application/views/reports/app_return.php"><?php echo "Approval Return"; ?></a></li>
			<li><a href="application/views/reports/approval_return_report.php"><?php echo "Approval Return Report"; ?></a></li>
			<li><a href="application/views/reports/custsales_report.php"><?php echo "Consolidate Sales Report (Customer Wise)"; ?></a></li>
			<li><a href="application/views/reports/item_sales_report.php"><?php echo "Consolidate Sales Report (Item Wise)"; ?></a></li>
			<li><a href="application/views/reports/catagorysales_report.php"><?php echo "Consolidate Sales Report (Category Wise)"; ?></a></li>
            
            <li><a href="application/views/reports/custrent_report1.php"><?php echo "Consolidate Rent Report (Customer Wise)"; ?></a></li>
			<li><a href="application/views/reports/item_rent_report.php"><?php echo "Consolidate Rent Report (Item Wise)"; ?></a></li>
			<li><a href="application/views/reports/catagoryrent_report.php"><?php echo "Consolidate Rent Report (Category Wise)"; ?></a></li>
            
			<li><a href="application/views/reports/rent.php"><?php echo "Rent"; ?></a></li>
			<li><a href="application/views/reports/rent_return.php"><?php echo "Rent Return"; ?></a></li>
			<li><a href="application/views/reports/bookingreport.php"><?php echo "Booking Status"; ?></a></li>
			<li><a href="application/views/reports/rent1.php"><?php echo "Scheme"; ?></a></li>
			<li><a href="application/views/reports/rent_return1.php"><?php echo "Scheme Return"; ?></a></li>
			<li><a href="application/views/reports/appReport.php"><?php echo "Approval Reports"; ?></a></li>
            <li><a href="application/views/reports/saleReport.php"><?php echo "Sales Reports"; ?></a></li>
			<li><a href="application/views/reports/rentReport.php"><?php echo "Rent Reports"; ?></a></li>
			<li><a href="application/views/reports/rentReport1.php"><?php echo "Scheme Report"; ?></a></li>
			<li><a href="application/views/reports/stock.php"><?php echo "Stock Report"; ?></a></li>
			<li><a href="application/views/reports/commReport.php"><?php echo "Commission Reports"; ?></a></li>
			<li><a href="application/views/reports/balance_amount.php" target="_new"><?php echo "Customer Balance Amount Reports"; ?></a></li>
<li><a href="application/views/reports/rent_amount.php" target="_new"><?php echo "Customer Rent Balance Amount Reports"; ?></a></li>
            <li><a href="application/views/reports/BeauReport.php"><?php echo "Beautician Reports"; ?></a></li>
             <li><a href="application/views/reports/custLst.php"><?php echo "Customer List"; ?></a></li>
              <li><a href="application/views/reports/Beaulst.php"><?php echo "Beautician List"; ?></a></li>
          <!--  <li><a href="application/views/reports/auditReport.php"><?php echo "Audit Entry"; ?></a></li>
            <li><a href="application/views/reports/audit.php"><?php echo "Audit Reports"; ?></a></li>-->
            <li><a href="application/views/reports/auditReportNew.php"><?php echo "Audit Entry"; ?></a></li>
            <li><a href="application/views/reports/auditReportViewNew.php"><?php echo "Audit Reports"; ?></a></li>
             <li><a href="application/views/reports/sms.php"><?php echo "Send SMS"; ?></a></li>
		</ul>
	</li>

	<li>Detailed Reports
		<ul>
			<li><a href="<?php echo site_url('reports/detailed_sales');?>"><?php echo $this->lang->line('reports_sales'); ?></a></li>
			<li><a href="<?php echo site_url('reports/specific_customer');?>"><?php echo $this->lang->line('reports_customer'); ?></a></li>
			<li><a href="<?php echo site_url('reports/specific_employee');?>"><?php echo $this->lang->line('reports_employee'); ?></a></li>
		</ul>
	
	</li>
	
	
	<li>GST
		<ul>
		<li><a href="application/views/reports/approvalnew.php">SALES</a></li> 
		
		<li><a href="application/views/reports/rentnew.php">Rent</a></li> 
		</ul>
	
	</li>
	
	
	 <li>Image Upload
		<ul>
			
           <li><a href="application/views/UploadImage/load-Img.php"><?php echo "Image Upload;" ?></a></li> 
            
		</ul>
	
	</li>
</ul>
<?php
if(isset($error))
{
	echo "<div class='error_message'>".$error."</div>";
}
?>
<?php $this->load->view("partial/footer"); ?>

<script type="text/javascript" language="javascript">
$(document).ready(function()
{
});
</script>