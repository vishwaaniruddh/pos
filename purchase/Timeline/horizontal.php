<?php
//  include('../config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();

$id=$_GET['id']; 



$opd=mysqli_query($con,"select * from pre-investigation where p_id='$id' order by current_date");

 function getExtension($str) {
         $a = strrpos($str,".");
         if (!$a) { return ""; }
         $l = strlen($str) - $a;
         $ext = substr($str,$a+1,$l);
         return $ext;
 }
?>
	
<!doctype html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="author" content="Made with ❤ by Jorge Epuñan - @csslab">
	
	<title>jQuery Timeline 0.9.51 - Dando vida al tiempo</title>
	<link rel="stylesheet" href="css/style.css" media="screen" />
	
	<script src="js/jquery.min.js"></script>
	<script src="js/jquery.timelinr-0.9.51.js"></script>
	<script>
		$(function(){
			$().timelinr({
				arrowKeys: 'true'
			})
		});
	</script>
</head>

<body><center>
<table width="904" height="46" border="0" ><tr><td width="595"><input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = '../view_patient1.php';" value="Go Back" />
<input type="button" style="width:110px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'horizontal.php?id=<?php echo $id; ?>';" value="Pre Investigation" />
<input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'opd_his.php?id=<?php echo $id; ?>';" value="OPD" />

<input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'admission.php?id=<?php echo $id; ?>';" value="Admission" />
<input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'surgery.php?id=<?php echo $id; ?>';" value="Surgery" />
</td>
<td width="175"><h1>History </h1></td></tr></table></center>
<div id="timeline">
		<ul id="dates"><?php
        
       while($opd1=mysqli_fetch_row($opd)){

?>
<li ><a href="#<?php echo $opd1[0]."o"; ?>"><?php if(isset($opd1[3]) and $opd1[3]!='0000-00-00') echo date('d-m-Y',strtotime($opd1[3])); ?> (Pre Investigation)</a>
           </li>
		<?php } ?>
        
        
        
		</ul>
		<ul id="issues"><!------------------------------------OPD--------------------------------------------------------------------------------------------------->
             <?php $opd2=mysqli_query($con,"select * from pre-investigation where p_id='$id' order by current_date");
		while($opd3=mysqli_fetch_row($opd2)){
			$pat=mysqli_query($con,"select * from patient where no='$id'");
			$pat1=mysqli_fetch_row($pat);
			
			$pdoc1=mysqli_query($con,"select * from doctor where doc_id='$opd3[2]'");
			$doc1=mysqli_fetch_row($pdoc1);
			?>
			<li id="<?php echo $opd3[0]."o"; ?>">
				
				<h1 style="font-size:19px;">Pre Investigation Detail</h1>
             <p> Patient Id : <b><?php echo $pat1[2]; ?></b>  &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;
             &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Patient Name :<b> <?php echo $pat1[6]; ?></b>
             </p><br/>
             
             <p>OPD Date: <b><?php if(isset($opd3[4]) and $opd3[4]!='0000-00-00') echo date('d-m-Y',strtotime($opd3[4])); ?></b>
             
         
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Reference By: <b><?php echo $doc1[1]; ?></b></p><br/>
             
           
            
             
             <p>Complaints : <b> <?php echo $opd3[5]; ?></b></p><br/>
             
           <p> Findings :<b> <?php echo $opd3[6]; ?></b>
              </p><br/>
             
             
         
             
             
             <p>Advised: :<b> <?php echo $opd3[7]; ?></b>   </p><br/>
            
            <p>Diagnosis:  <b><?php echo $opd3[8]; ?> </b>  </p><br/>
             
             
             <p>Medicine Name:<b><?php echo $med; ?> </b>
  <p>Reports :</p>
    <?php
	$photo=mysqli_query($con,"select * from pre_invest_report where patient_id='$id' and pre_id='$opd3[0]'");
	while($photo1=mysqli_fetch_row($photo)){ 
	if($photo1[2]==""){ }else {
		
		
  if ($photo1[2]) 
 	{// echo $image;
 	//get the original name of the file from the clients machine
 		$filename = stripslashes($photo1[2]);
 	//get the extension of the file in a lower case format
  		$extension = getExtension($filename);
 		$extension = strtolower($extension);
 	//if it is not a known extension, we will suppose it is an error and will not  upload the file,  
	//otherwise we will do more tests
 if (($extension != "jpg") && ($extension != "jpeg") && ($extension != "png") && ($extension != "gif")) 
 		{
		//print error message ?>
 			
            <a href="../<?php echo $photo1[2]; ?>" target="_blank">Your Pdf</a>

 		<?php 
 		}
 		else
 		{ ?>
  
    
    <a href="../<?php echo $photo1[2]; ?>" target="_blank"><img src="../<?php echo $photo1[2]; ?>" width="256" height="256" /></a>
    <?php } } } }?>
		  </li>
			<?php } ?>
			
		</ul>
<div id="grad_left"></div>
		<div id="grad_right"></div>
		<a href="#" id="next">+</a>
		<a href="#" id="prev">-</a>
	</div>

	<?php CloseCon($con);?>

</body>
</html>