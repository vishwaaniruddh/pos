<?php
//  include('../config.php');
 include('../../db_connection.php') ;
$con=OpenSrishringarrCon();

$id=$_GET['id']; 

$sur=mysqli_query($con,"select * from surgery1 where no='$id'  order by sur_date");




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
	<script language="javascript" type="text/javascript">
        function printDiv(divID) {
            //Get the HTML of div
            var divElements = document.getElementById(divID).innerHTML;
            //Get the HTML of whole page
            var oldPage = document.body.innerHTML;

            //Reset the page's HTML with div's HTML only
            document.body.innerHTML = 
              "<html><head><title></title></head><body>" + 
              divElements + "</body>";

            //Print Page
            window.print();

            //Restore orignal HTML
            document.body.innerHTML = oldPage;

          
        }
    </script>
</head>

<body><center>
<!--<table width="904" height="46" border="0" ><tr><td width="595"><input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = '../view_patient.php';" value="Go Back" />
<input type="button" style="width:110px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'horizontal.php?id=<?php echo $id; ?>';" value="Pre Investigation" />
<input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'opd_his.php?id=<?php echo $id; ?>';" value="OPD" />

<input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'admission.php?id=<?php echo $id; ?>';" value="Admission" />


<input type="button" style="width:100px; height:30px; color:#ac0404;" onClick="javascript:location.href = 'surgery.php?id=<?php echo $id; ?>';" value="Surgery" />
<input type="button" value="Print" onClick="javascript:printDiv('issues')" style="width:100px; height:30px; color:#ac0404;"/>
</td>
<td width="175"><h1>History </h1></td></tr></table>--></center>
<div id="timeline">
		<ul id="dates">
        
		<?php 
        while($sur1=mysqli_fetch_row($sur)){
///echo $sur1[0];
?>
<li ><a href="#<?php echo $sur1[38]; ?>"><?php if(isset($sur1[5]) and $sur1[5]!='0000-00-00') echo date('d-m-Y',strtotime($sur1[5])); ?> (Srgry)</a>
          </li>
		<?php } ?>
        
        
        
		</ul>
		<ul id="issues">
        <?php $adm2=mysqli_query($con,"select * from surgery1 where no='$id'  order by sur_date");
		while($adm3=mysqli_fetch_row($adm2)){
			$pat=mysqli_query($con,"select * from patient where srno='$id'");
			$pat1=mysqli_fetch_row($pat);
			
			$pdoc=mysqli_query($con,"select * from doctor where doc_id='$adm3[3]'");
			$doc=mysqli_fetch_row($pdoc);
			$time=$adm3[9];
list($hr, $min) = explode(":", $time);

$time1=$adm3[10];
list($hr1, $min1) = explode(":", $time1);

			?>
			<li id="<?php echo $adm3[38]; ?>">
				
				<h1 style="font-size:19px;">Surgery</h1>
                
             <p>
             Patient Id : <b><?php echo $pat1[3]; ?></b>  &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
             Patient Name :<b> <?php echo $pat1[6]; ?></b>&nbsp;&nbsp;
             Doctor Name: <b><?php echo $doc[1]; ?></b></p><br/>
             
             <p>Date: <b><?php if(isset($adm3[5]) and $adm3[5]!='0000-00-00') echo date('d-m-Y',strtotime($adm3[5])); ?></b>
             
         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="timein">Time In :</span><b><?php echo $adm3[9]; ?></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="timeout">Time Out :</span><b> <?php echo $adm3[10]; ?></b></p>
             <br/>
           <?php
           $result1 = mysqli_query($con,"select doc_id,name from doctor where doc_id='$adm3[7]'");
           $row1=mysqli_fetch_row($result1);
           
           $result6 = mysqli_query($con,"select doc_id,name from doctor where doc_id='$adm3[3]'");
           $row6=mysqli_fetch_row($result6);
           
           $result12=mysqli_query($con,"select * from doctor where doc_id='$adm3[3]'");
           $row12=mysqli_fetch_row($result12);
           
$result13=mysqli_query($con,"select * from doctor where doc_id='$adm[4]'");
$row13=mysqli_fetch_row($result13);
           ?>  
           
             <p><span class="an">Anaesthetist </span> :<b> <?php echo $row1[1]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="surgeon1">Surgeon 1:</span><b> <?php echo $row6[1];; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
 
 <span class="surgeon2">Surgeon 2:</span><b> <?php echo $row7[1]; ?></b></p><br/>
             
             <p>Type: <b><?php if($row[8]=="LA"){ echo "Local Anaesthetist (LA)"; } ?>
                 <?php if($row[8]=="GA"){ echo "General Anaesthetist (GA)"; } ?> 
                 <?php if($row[8]=="SA"){ echo "Spinal Anaesthetist (SA)"; } ?></b> 
                 
                 </p>
             <p><span class="procedure">Procedure :</span><b> <?php echo $adm3[6]; ?></b>
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p><br/>
             
             <p >Admission Fees :&nbsp;
                 <?php echo $adm3[30]; ?>
                  </p>
                 <p >  ECG Charges :<?php echo $adm3[20]; ?></p>
                <p >Pulse OX Charges :<?php echo $adm3[13]; ?></p>
                  <p >Pathology Charges :
                 <?php echo $adm3[21]; ?></p>
                <p >OT & Instrument :<?php echo $adm3[12]; ?></p>
                <p >Dressing Charges :<?php echo $adm3[22]; ?></p>
                <p >Material & Drugs :<?php echo $adm3[15]; ?></p>
                  <p >Routine Nursing Charges :<?php echo $adm3[23]; ?></p>
                 <p >Surgery Charges :<?php echo $adm3[14]; ?></p>
                   <p >Spl. Nursing Charges :<?php echo $adm3[18]; ?></p>
                <p >Anaesthesis Charges :<?php echo $adm3[17]; ?></p>
                  <p >Expert Visit Charges :<?php echo $adm3[24]; ?></p>
                 <p >Lithotripsy Charges :<?php echo $adm3[16]; ?></p>
                   <p >Physiotherapy Charges :<?php echo $adm3[25]; ?></p>
                 <p >X-Ray Charges :<?php echo $adm3[19]; ?></p>
                  <p >Ambulance Charges :<?php echo $adm3[27]; ?></p>
                 <p >Misc. Charges :<?php echo $adm3[28]; ?></p>
                   <p >Total : <?php echo $adm3[29]; ?></p>
                 <p >Discount :    <?php echo $adm3[31]; ?></p>
                  <p >Grand Total :
                    <?php echo $adm3[32]; ?></p>
                <br/>
             
           
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