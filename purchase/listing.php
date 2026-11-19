<?php $this->load->view("partial/header"); ?>
<div id="page_title" style="margin-bottom:8px;"><?php echo $this->lang->line('purchase_purchase'); ?></div>
<div id="welcome_message"><?php echo $this->lang->line('purchase_welcome_message'); ?>
<ul id="report_list">
	
	
	<br/><br/><br/><ul><li><h3>Purchase Section</h3><br/>
	<ul>
	<li><a href="application/views/purchase/purchase_entry.php">Supplier`s Bill Entry</a></li>
	<li><a href="application/views/purchase/view_bills.php">View Supplier`s Bill </a></li>
    <li><a href="application/views/purchase/view_payments.php">View Supplier`s Payments </a></li>
	</ul></li>
	<br><br>
	<li>
	
	<h3>Banking Section</h3><br/>
	<ul><li>
	<a href="application/views/purchase/bank_entry.php"> Bank Transactions</a><br/></li>
	<li><a href="application/views/purchase/bank_report.php"> Bank Report</a><br/></li>
	<li><a href="application/views/purchase/bank_concile.php">Reconcile Bank Transactions</a><br/></li></ul>
	</li></ul>
	
	

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