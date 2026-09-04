<?php
//  include('../config.php');
include('../../db_connection.php') ;
$con=OpenSrishringarrCon();
 
 
$id=$_GET['id']; 


$adm=mysqli_query($con,"select * from admission where patient_id='$id'  order by admit_date");
$opd=mysqli_query($con,"select * from opd where patient_id='$id' order by opddate");
$num=mysqli_num_rows($opd);
$sur=mysqli_query($con,"select * from surgery1 where no='$id'  order by sur_date");
 function getExtension($str) {
         $a = strrpos($str,".");
         if (!$a) { return ""; }
         $l = strlen($str) - $a;
         $ext = substr($str,$a+1,$l);
         return $ext;
 }
 
 CloseCon($con);
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
<table width="904" height="46" border="0" ><tr><td width="595"><input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = '../view_patient.php';" value="Go Back" />
<input type="button" style="width:110px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'horizontal.php?id=<?php echo $id; ?>';" value="Pre Investigation" />
<input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'opd_his.php?id=<?php echo $id; ?>';" value="OPD" />

<input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'admission.php?id=<?php echo $id; ?>';" value="Admission" />


<input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'surgery.php?id=<?php echo $id; ?>';" value="Surgery" />
</td>
<td width="175"><h1>History </h1></td></tr></table></center>
<div id="timeline">
		<ul id="dates">
        <?php while($adm1=mysqli_fetch_row($adm)){

?>
			<li ><a href="#<?php echo $adm1[0]."a"; ?>"><?php if(isset($adm1[3]) and $adm1[3]!='0000-00-00') echo date('d-m-Y',strtotime($adm1[3])); ?> (Admit)</a>
           </li>
		<?php } ?>        
        
        
		</ul>
		<ul id="issues">
        <?php $adm2=mysqli_query($con,"select * from admission where patient_id='$id'");
		while($adm3=mysqli_fetch_row($adm2)){
			$pat=mysqli_query($con,"select * from patient where srno='$id'");
			$pat1=mysqli_fetch_row($pat);
			
			$pdoc=mysqli_query($con,"select * from doctor where doc_id='$adm3[2]'");
			$doc=mysqli_fetch_row($pdoc);
			?>
			<li id="<?php echo $adm3[0]."a"; ?>">
				
				<h1 style="font-size:19px;">Addmission</h1>
                
             <p>
             Patient Id : <b><?php echo $pat1[3]; ?></b>  &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Patient Name :<b> <?php echo $pat1[6]; ?></b>&nbsp;&nbsp;
             Doctor Name: <b><?php echo $doc[1]; ?></b></p><br/>
             
             <p>Addmission Date: <b><?php if(isset($adm3[2]) and $adm3[2]!='0000-00-00') echo date('d-m-Y',strtotime($adm3[2])); ?></b>
             
         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             
             Addmission Time: <b><?php echo $adm3[3]; ?></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             
             Room No: <b> <?php echo $adm3[6]; ?></b></p><br/>
             
           
             <p>Discharge Date :<b> <?php if(isset($adm3[4]) and $adm3[4]!='0000-00-00') echo date('d-m-Y',strtotime($adm3[4])); ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             
             Discharge Time :<b> <?php echo $adm3[5]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
 <?php 
$startDate = strtotime($adm3[2]);
$endDate = strtotime($adm3[4]);

$interval = $endDate - $startDate;
$days = floor($interval / (60 * 60 * 24));

?>           
             Stay In Days :<b> <?php echo $days; ?></b></p><br/>
             
             
             <p>Final Diagnosis : <b> <?php echo $adm3[10]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            Allergies :<b> <?php //echo $adm3[10]; ?></b>
              </p><br/>
             
             
             <p>Symptoms of Present Illness :<b> <?php //echo $adm3[11]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            Past Illness :<b> <?php echo $adm3[7]; ?></b>
              </p><br/>
             
             
             <p>Systematic Examination :<b> <?php //echo $adm3[13]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Local Examination :<b> <?php //echo $adm3[14]; ?></b>
              </p><br/>
             
             
             <p>Provisional Diagnosis :<b> <?php echo $adm3[13]; ?></b>   </p><br/>
            
            <p>General Examination :    </p><br/>
             
             
             <p>Built :<b> <?php //echo $adm3[16]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            Temperature :<b> <?php //echo $adm3[17]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Nourishment :<b> <?php //echo $adm3[18]; ?></b></p><br/>
             
             
             <p>Pulse :<b> <?php //echo $adm3[19]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            Anaema :<b> <?php //echo $adm3[20]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Respiration :<b> <?php //echo $adm3[21]; ?></b></p><br/>
             
             <p>Cyanosis :<b> <?php //echo $adm3[22]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
           Lying BP Down :<b> <?php //echo $adm3[23]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Oedema :<b> <?php //echo $adm3[24]; ?></b></p><br/>
             
              <p>BP Sitting :<b> <?php //echo $adm3[25]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          Jaundice :<b> <?php //echo $adm3[26]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Skin :<b> <?php //echo $adm3[27]; ?></b></p><br/>
             
              <p>Throat :<b> <?php //echo $adm3[28]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          Nails :<b> <?php //echo $adm3[29]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Tongue :<b> <?php //echo $adm3[30]; ?></b></p><br/>
             
              <p>Other :<b> <?php //echo $adm3[31]; ?></b>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
         Lymph Nodes :<b> <?php //echo $adm3[32]; ?></b>
       </p>
            
	
			</li>
			<?php } ?>
       	
		</ul>
<div id="grad_left"></div>
		<div id="grad_right"></div>
		<a href="#" id="next">+</a>
		<a href="#" id="prev">-</a>
	</div>

	

</body>
</html>