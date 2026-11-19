<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include ('../db_connection.php');
$con = OpenSrishringarrCon();





// var_dump($_REQUEST);

// return ;

$company_name = $_POST['company_name'];
$invoice_type = 'sell'; 
$created_by = 1;

// $by = $_POST['by'];




// $year = date("Y");
// $month = date("m");

// if ($month >= 4) { 
//     $financial_year = substr($year, 2);
// } else {
//     $financial_year = substr($year - 1, 2);
// }




// if ($company_name === "Sri Shringarr") {
//     $prefix = "SSFS-" . $financial_year;
// } elseif ($company_name === "SAKAR TRADE LINK") {
//     $prefix = "SAK-" . $financial_year;
// } else {
//     die("Invalid company name.");
// }

// $suffix = ($invoice_type === "rent") ? "/R-" : "/S-";

// $sql = "SELECT invoice_number FROM invoice_tracker 
//         WHERE company = '$company_name' 
//         AND invoice_type = '$invoice_type' 
//         AND invoice LIKE '$prefix%' 
//         ORDER BY id DESC LIMIT 1";

// $result = $con->query($sql);

// if ($result->num_rows > 0) {
//     $row = $result->fetch_assoc();
//     $last_invoice_number = (int)$row["invoice_number"] + 1;
// } else {
//     $last_invoice_number = 1;
// }


// $invoice_number = str_pad($last_invoice_number, 5, "0", STR_PAD_LEFT);
// $full_invoice = $prefix . $suffix . $invoice_number;





$by = $_POST['by'];
$company_name = $_POST['company_name'] ?? 'Sri Shringarr';
$invoice_type = $_POST['invoice_type'] ?? 'sell'; // rent, sell, or approval

$year = date("Y");
$month = date("m");
$financial_year = ($month >= 4) ? substr($year, 2) : substr($year - 1, 2);


if ($by == "QUOTATION") {
  $bs = 'S';
} else {
  $bs = 'A';
}



if ($bs == "A" && $company_name === "Sri Shringarr") {
    $prefix = "APP-" . $financial_year;
    $suffix = "/A-";
} else {
    if ($company_name === "Sri Shringarr") {
        $prefix = "SSFS-" . $financial_year;
    } elseif ($company_name === "SAKAR TRADE LINK") {
        $prefix = "SAK-" . $financial_year;
    } else {
        die("Invalid company name.");
    }
    $suffix = ($invoice_type === "rent") ? "/R-" : "/S-";
}

$sql = "SELECT invoice_number FROM invoice_tracker 
        WHERE company = '$company_name' 
        AND invoice_type = '$invoice_type' 
        AND invoice LIKE '$prefix%' 
        ORDER BY id DESC LIMIT 1";

$result = $con->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_invoice_number = (int)$row["invoice_number"] + 1;
} else {
    $last_invoice_number = 1;
}

$invoice_number = str_pad($last_invoice_number, 5, "0", STR_PAD_LEFT);
$full_invoice = $prefix . $suffix . $invoice_number;


$insert_sql = "INSERT INTO invoice_tracker (company, invoice_type, invoice_number, invoice, created_at, created_by) 
               VALUES ('$company_name', '$invoice_type', '$last_invoice_number', '$full_invoice', NOW(), '$created_by')";




if ($con->query($insert_sql) === TRUE) {
    echo "Invoice Created: " . $full_invoice;


$qty1 = 0;
$cid = $_POST['cid'];

$design = $_POST['design'];
$cname = $_POST['cname'];
$acc = $_POST['acc'];

$amt = $_POST['amt'];

$bill_prepare_by = $_POST['bill_prepare_by'];

$resultname = mysqli_query($con, "SELECT * FROM  `phppos_people` where person_id='$cid'");
$rowname = mysqli_fetch_row($resultname);

if (isset($_POST['dyes'])) {
  $dis_st = $_POST['dyes'];
} else {
  $dis_st = $_POST['dno'];
}
if (isset($_POST['itemwise'])) {
  $wise = $_POST['itemwise'];
} else {
  $wise = $_POST['totalwise'];
}
$prz = $_POST['qty'];

$unit_price = $_POST['prz'];

$d = count($design);
$dis = $_POST['dis'];
$disper = $_POST['disper'];
$pay_by = $_POST['pay_By'];
$note = $_POST['note'];
$amountTotal = $_POST['amountTotal'];
 $_odate = $_POST['bill_date'];
// echo '<br />';

$todaysDate = date('Y-m-d');


$odateAr = explode("/",$_odate);

$odate = '';

foreach($odateAr as $odateArKey=>$odateArVal){
    $odate .=  $odateArVal .'-'; 
}

$odate .='sri' ;

$odate = str_replace("-sri", "" , $odate ) ;

$odate = date("Y-m-d", strtotime($odate));





$myflag = true;
$dname = "";
$dqty = 0;
$bqty = 0;
for ($i = 0; $i < $d; $i++) {
  $a = $prz[$i] . ".00";
  $sq = "SELECT quantity FROM phppos_items WHERE name='$design[$i]' and  is_deleted = 0";
  $res2 = mysqli_query($con, $sq);
  $det = mysqli_fetch_row($res2);

  if ($a > $det[0]) {
    $dname = $design[$i];
    $dqty = $a;
    $bqty = $det[0];
    $myflag = false;
    break;
  }
}

if (!$myflag) {
  echo "<br><br><br><center>You don't have enough quantity for " . $dname . ", required  " . $dqty . ", in Stock  (" . $bqty;
  echo "). Go back and try again</center>";

}
// mysqli_query($con,"BEGIN;");
mysqli_autocommit($con, FALSE);

$cardperc = "0";
if (isset($_POST['pay_By'])) {
  if ($_POST['pay_By'] == "Card") {

    $cardperc = "2";
  }
}

if($myflag) {
  //echo "insert into `approval`(cust_id,bill_date,status,paid_amount,discount_status,discount_wise,discount_per,bill_by,pay_by,note,card_perc,card_amt) values('$cid',STR_TO_DATE('".$odate."','%d/%m/%Y'),'$bs','$amt','$dis_st','$wise','$disper','$by','$pay_by','$note','".$cardperc."','".$_POST['cardpercamt']."')" ; 
  $t1 = mysqli_query($con, "insert into `approval`(cust_id,bill_date,status,paid_amount,discount_status,discount_wise,discount_per,bill_by,pay_by,note,card_perc,card_amt,amountTotal,new_bill_number,company_name,bill_prepare_by) 
  values('$cid','" . $odate . "','$bs','$amt','$dis_st','$wise','$disper','$by','$pay_by','$note','" . $cardperc . "','" . $_POST['cardpercamt'] . "','".$amountTotal."','".$full_invoice."','".$company_name."','".$bill_prepare_by."')");


$thisBillID = $con->insert_id ; 

  $result1 = mysqli_query($con, "SELECT max(bill_id) FROM  `approval` where cust_id='$cid'");
  $rowordno = mysqli_fetch_row($result1);

  if ($amt == "" || $amt == "0") {
    $t2 = true;
  } else {

    $t2 = mysqli_query($con, "insert into `paid_amount`(bill_id,amount,return_date,payment_by,bid,new_bill_number) values('$cid','$amt','".$todaysDate."','$pay_by','".$thisBillID."','".$full_invoice."')");

    if ($t2) {
      // echo "INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`) VALUES ('','".$acc."','receit','".$amt."',STR_TO_DATE('".$odate."','%d/%m/%Y'),'payment from customer $rowname[0] $rowname[1]','NO',now())" ; 
      $t3 = mysqli_query($con, "INSERT INTO `bank_transaction`(`trans_id`, `bank_id`, `trans_type`, `trans_amt`, `trans_date`, `trans_memo`, `reconcile`,`enrty_date`,bill_id,new_bill_number) 
      VALUES ('','" . $acc . "','receit','" . $amt . "','" . $odate . "','payment from customer $rowname[0] $rowname[1]','NO',now(),'".$thisBillID."','".$full_invoice."')");
    }

  }


  $result = mysqli_query($con, "SELECT * FROM  `phppos_people` where person_id='$cid'");
  $row = mysqli_fetch_row($result);
  $sum2 = 0;
  $total22 = 0;
  for ($j = 0; $j < $d; $j++) {

    $sq22 = "SELECT * FROM phppos_items WHERE name='$design[$j]' and is_deleted = 0";
    $res22 = mysqli_query($con, $sq22);
    $num22 = mysqli_num_rows($res22);
    $row12 = mysqli_fetch_row($res22);

    $total22 = (float)$row12[6] * (float)$prz[$j];

    $sum2 += $total22;
  }

  ///echo $sum2;
  $c = (float)$dis / (float)$sum2;
  $b = (float)$c * 100;
  //echo $sum."/".$b."<br/>";

  ?>
  <!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
  <html xmlns="https://www.w3.org/1999/xhtml">

  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>SHRINGAAR</title>
  </head>
  <script type="text/javascript" src="paging.js"></script>
  <script type="text/javascript">
    function PrintDiv() {
      var divToPrint = document.getElementById('bill');
      divToPrint.style.fontSize = "10px";
      var popupWin = window.open('', '_blank', 'width=800,height=500');
      popupWin.document.open();
      popupWin.document.write('<html><body onload="window.print()">' + divToPrint.innerHTML + '</html>');
      popupWin.document.close();
    }
    function rollback() {
      document.getElementById("bdy").innerHTML = "Transaction is rolled back, keeping the data secure. Please refresh this page to complete the transaction!";
    }

  </script>

  <body id="bdy" >

    <div id="bill" style="font-size:12px; display:none;">
      <table width="787" border="0" align="center">
        <tr>
          <td width="781" height="42">

            <table width="780">
              <tr>
                <td colspan="3" align="center" style="padding-left:100px;">
                  <font size="2">
                    <B><U> <?php echo $by; ?> </U></B>
                  </font>
                </td>
              </tr>
              <tr>
                <!--<td width="346" align="left" valign="top"><b>-->
                <!--  <p><font size="-1" >MANUFACTURERS AND RETAILERS</font>-->
                <!--    <font size="-1">OF BRIDAL SETS</font>,<br />-->
                <!--    <font size="-1">HAIR ACCESSORIES AND  BROOCHES,</font><br/>-->
                <!--    <font size="-1">BRIDAL DUPATTAS</font>,<br />-->
                <!--    <font size="-1">CHANIYA CHOLI,<br/>&amp; ALL KINDS OF ACCESSARIES.</font><br />-->
                <!--  </p></b></td>-->



                <!--<td height="42" colspan="2" align="right" style="padding-left:10px;" valign="bottom"><img src="bill.PNG" width="408" height="165"/></td>-->
                <td style="padding:0px; margin:0px;">
                  <div style="text-align:left;font-size:14px;"> <b>SRI SHRINGARR FASHION STUDIO</b> </div><br />
                  <div style="text-align:left;font-size:14px;"> <b>UPI ID: srishringarrfashionstudio@icici</b> </div>
                  <br />
                  <div style="text-align:left;padding-left:50px"> <img src="img/sri_qr_icici.jpg" width="80px"> </div>
                </td>

                <td style=" padding: 0px; margin:0px; padding-left: 50px; ">
                  <img src="bill.PNG" width="250px" style="padding-right:80px">
                </td>

                <td style="padding:0px; margin:0px; " style="font-size:11px;">
                  <div><b><u style="font-size:11px;">Bank Account Details</u></b></div>
                  <div style="font-size:10px;">SRI SHRINGARR FASHION STUDIO</div>
                  <div style="font-size:11px;">Account number: 756305000529</div>
                  <div style="font-size:11px;">IFSC: ICICI0007563</div>
                  <div style="font-size:11px;">Bank name: ICICI BANK</div>
                  <div style="font-size:11px;">Branch: VILE PARLE EAST BRANCH</div>
                </td>
              </tr>

              <tr>
                <td colspan="2"></td>
              </tr>

              <tr>
                <td height="21" colspan="2">
                  <font size="3">M/s.&nbsp;:&nbsp;&nbsp;<b><?PHP echo $row[0] . " " . $row[1]; ?></b></font>
                </td>
                <td width="128">
                  <font size="2">Date: </font><b><?php echo date('d-m-Y',strtotime($odate));; ?></b>
                </td>
              </tr>

              <tr>
                <td colspan="2" height="23">
                  <font size="3">Address</font>
                  <font size="2">.: &nbsp;&nbsp;&nbsp;
                    <b><?php echo $row[4]; ?><br /><?php echo $row[6]; ?><?php echo $row[8]; ?>
                      <?php echo $row[9]; ?></b>
                  </font>
                </td>
                <td>
                  <font size="3">Bill No:</font><b><?php echo $full_invoice; ?></b>
                </td>
              </tr>
              <tr>
                <td>
                  <font size="3">Contact No.: &nbsp;&nbsp;&nbsp; <b><?php echo $row[2]; ?></b></font>
                </td>
                <td width="290"></td>
              </tr>
            </table>
            <font size="2">
              <table width="780" border="1" cellpadding="4" cellspacing="0" id="results">
                <tr>
                  <th width="49">
                    <font size="2">Sr. No.</font>
                  </th>
                  <th width="135">
                    <font size="2">ITEM CODE</font>
                  </th>
                  <!--<th width="130">PARTICULARS</th>-->

                  <th width="112">
                    <font size="2">PRICE</font>
                  </th>
                  <th width="75">
                    <font size="2">QTY</font>
                  </th>
                  <th width="102">
                    <font size="2">AMOUNT</font>
                  </th>
                  <th width="112">
                    <font size="2">DISCOUNT</font>
                  </th>
                  <th width="112">
                    <font size="2">Discount per Item</font>
                  </th>
                  <th width="123">
                    <font size="2">Total Amount</font>
                  </th>

                </tr>
                <?php
                $j = 1;
                $total = 0;
                $s2 = 0;
                ///$ds=$dis/$d;
                for ($i = 0; $i < $d; $i++) {
                  $total1 == 0;

                  $a = $prz[$i] . ".00";
                  $sq = "SELECT * FROM phppos_items WHERE name='$design[$i]' and is_deleted = 0";
                  $res2 = mysqli_query($con, $sq);
                  $num2 = mysqli_num_rows($res2);
                  $row1 = mysqli_fetch_row($res2);
                  /////discount no
                  if ($dis_st == "no") {
                    $dis = "0";
                    $wise = "";
                    $disper = "";
                    $dis = "";
                    $itemper = "";
                    $total1 = (float)$row1[6] * (float)$prz[$i];
                    $sum = (float)$total1 - (float)$p;
                    //echo $dis_st."/".$wise."/".$total1;
/////////////////////////discount Yes
                  } else {

                    //echo $dis_st."/".$wise;
/////////////////////////discount item wise
                    if ($wise == "Item Wise") {
                      $dis1 = $_POST['dis1'];



                      $desq = $design[$i];

                      $itemper = $_POST[$desq];
                      ///echo $desq."/".$itemper."<br/>";
              
                      if ($itemper == "%") {
                        //echo $row1[6]."*".$prz[$i]."*".($dis1[$i]/100.0)."<br/>";
                        $p = round((float)$row1[6] * (float)$prz[$i] * ((float)$dis1[$i] / 100.0));

                        $total1 = (float)$row1[6] * (float)$prz[$i];
                        $sum = (float)$total1 - (float)$p;
                      } else {

                        $p = $dis1[$i];

                        $total1 = (float)$row1[6] * (float)$prz[$i];
                        $sum = (float)$total1 - (float)$p;

                      }
                      //echo $p."/".$sum."<br/>";
/////////////////////////discount total  wise
                    } else {

                      /////////////////////////discount total wise in Rs
                      if ($disper == "Rs") {
                        $ds = $b;
                        $p = round((float)$row1[6] * (float)$prz[$i] * ((float)$b / 100.0));

                        $total1 = (float)$row1[6] * (float)$prz[$i];
                        $sum = (float)$total1 - (float)$p;
                        $itemper = "";
                        //echo $p."/".$sum."<br/>";
/////////////////////////discount total wise in %
                      } else {
                        //echo $row1[6]."*".$prz[$i]."*".($dis/100.0)."<br/>";
                        $p = round((float)$row1[6] * (float)$prz[$i] * ((float)$dis / 100.0));
                        //echo $p."<br/>";
                        $total1 = (float)$row1[6] * (float)$prz[$i];
                        $sum = (float)$total1 - (float)$p;
                        $itemper = "";
                      }

                    }




                  }


                  //echo "update  phppos_items  set quantity=quantity-$a WHERE name=".$design[$i];
              
                  $t3 = mysqli_query($con, "update `phppos_items` set quantity=quantity-$a WHERE name='" . $design[$i] . "'");

                  if ($dis_st == "no") {
                    $ds = 0;
                  } else {
                    if ($wise == "Item Wise") {

                      $ds = $dis1[$i];
                    } else {
                      if ($disper == "Rs") {

                      } else {
                        $ds = $dis;
                      }
                    }
                  }



                  ///echo $ds."/ ".$design[$i]." / ".$prz[$i]." / ".$p."<br/>";
              


$t4 = mysqli_query($con, "insert into approval_detail(bill_id,item_id,qty,discount,dis_amount,amount,item_per,final_amount,new_bill_number) 
                  values('$rowordno[0]','$design[$i]','$prz[$i]','$ds','$p','$sum','$itemper','$unit_price[$i]','".$full_invoice."')");
                  
                //   $t4 = mysqli_query($con, "insert into approval_detail(bill_id,item_id,qty,discount,dis_amount,amount,item_per,final_amount,new_bill_number) 
                //   values('$rowordno[0]','$design[$i]','$prz[$i]','$ds','$p','$sum','$itemper','$sum','".$full_invoice."')");
                  
                    $productsql = mysqli_query($con, "Select * from phppos_items where name='" . $design[$i] . "'");
                                    $productsqlResult = mysqli_fetch_assoc($productsql);

                                    $categoryName = $productsqlResult['category'];
                                    $categorysql = mysqli_query($con, "select * from categories where category='" . $categoryName . "'");
                                    $categorysqlResult = mysqli_fetch_assoc($categorysql);
                                    $productType = $categorysqlResult['typ'];

                                    $totalProductAmount = $sum;
                                    if ($productType == 1) {

                                        $thisProductTotalTaxable = $totalProductAmount / 1.03;
                                        $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.03;
                                        $cgst = $sgst = $thisProductTotalGst / 2;
                                    } else if ($productType == 2) {
                                        
                                        if($totalProductAmount>2500){
                                            $thisProductTotalTaxable = $totalProductAmount / 1.18;
                                            $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.18;                                            
                                        }else{
                                            $thisProductTotalTaxable = $totalProductAmount / 1.05;
                                            $thisProductTotalGst = $igst = $thisProductTotalTaxable * 0.05;
                                        }

                                        $cgst = $sgst = $thisProductTotalGst / 2;
                                    }
                                    $roundCGST = round($cgst, 0);

                                    $totalTaxableAmount = $totalTaxableAmount + $thisProductTotalTaxable;
                                    $totalCGST = $totalCGST + $cgst;
                                    $totalGST = (float)$totalGST + (float)$thisProductTotalGst;

                                    if ($company_name == 'SAKAR TRADE LINK') {

                                        
                                        // isFlyrobeProduct 

                                        $netAmount = $thisProductTotalTaxable;
                                        
                                        
                                        $phpitemssql = mysqli_query($con, "select supplier_id from phppos_items where name='" . $design[$i] . "'");
                                        $phpitemssql_result = mysqli_fetch_assoc($phpitemssql);
                                        $supplier_id = $phpitemssql_result['supplier_id'];


                                        $isFlyrobeProduct = ($supplier_id == '242875') ? '1' : '0';
                                        // End is flyrobe

                                        // Calcuate Commissions
                                        // $commission = ($isFlyrobeProduct == 1) ? '35%' : '8%';
                                        $commission = ($isFlyrobeProduct == 1) ? '43%' : '8%';
                                        $commision_amount = (float)$netAmount * ($commission / 100);

                                        // 

                                        $purchase_date = date('Y-m-d');


                                        echo $comsqlquery = "INSERT INTO flyrob_commission_sell(productType,sku,purchase_id,totalProductAmount, netAmount,isFlyrobeProduct,commision,commision_amount,purchase_date,status,company_name) 
     values('" . $productType . "','" . $design[$i] . "','" . $rowordno[0] . "','" . $totalProductAmount . "','" . $netAmount . "','" . $isFlyrobeProduct . "','" . $commission . "','" . $commision_amount . "','" . $purchase_date . "','Visible','".$company_name."')";

                                        mysqli_query($con, $comsqlquery);
                                        
                                        $overallCommisionAmount = (float)$netAmount - (float)$commision_amount;

                                        $overallCommisionAmount = round($overallCommisionAmount);

                                        // mysqli_query($con, "update approval_detail set commission_amt='" . $overallCommisionAmount . "' where bill_id='" . $rowordno[0] . "' and item_id='" . $design[$i] . "'");
                                    }



                                    // END FLYRBE COMMISSION PART 
                                    
                                    
                                    
                                    
                  $s1 = (float)$row1[6] * (float)$prz[$i];
                  // echo $p." / ". $sum."<br/>";
                  ?>
                  <tr>
                    <td>
                      <font size="2"><?php echo $j++; ?></font>
                    </td>
                    <td align="center">
                      <font size="2"><?php echo $row1[0]; ?></font>
                    </td>
                    <!--<td align="center"><?php //echo $row1[1]; ?></td>-->
                    <td align="center">
                      <font size="2"><?php echo $row1[6] ?></font>
                    </td>
                    <td align="center">
                      <font size="2"><?php echo $prz[$i];
                      $qty1 += $prz[$i]; ?></font>
                    </td>
                    <td align="center">
                      <font size="2"><?php echo $s1;
                      $s2 += $s1; ?></font>
                    </td>
                    <td align="center">
                      <font size="2"><?php
                      if ($dis_st == "no") {
                        echo "0%";
                      } else {
                        if ($wise == "Item Wise") {
                          if ($itemper == "%") {

                            echo $dis1[$i] . "%";
                          } else {
                            echo "Rs." . $dis1[$i];
                          }
                        } else {
                          if ($disper == "Rs") {
                            echo "Rs." . $p;
                          } else {
                            echo $dis . "%";
                          }
                        }
                      } ?></font>
                    </td>
                    <td align="center"><?php echo round((float)$p / (float)$prz[$i]); ?></td>
                    <td align="center">
                      <font size="2"><?php echo $sum; ?></font>
                    </td>
                  </tr>
                  <?php $total += $sum;
                } ?>
                <tr>
                  <td colspan="3" align="right">
                    <font size="2"><b>Total Qty : <?php echo $qty1; ?></b></font>
                  </td>
                  <td colspan="3" align="right">
                    <font size="2"><b>Gross Total: <?php echo "Rs." . $s2; ?> </b></font>
                  </td>
                  <td colspan="" align="right">
                    <font size="2"><b><?php if ($_POST['cardpercamt'] > 0) {
                      echo "Total";
                    } else {
                      echo "Net Payable";
                    } ?>:
                  </td>

                  <td align="right">
                    <?php echo "Rs." . $total; ?> </b>
            </font>
          </td>

        </tr>



        <?php if ($_POST['cardpercamt'] > 0) {

          $total = $total + $_POST['cardpercamt'];
          ?>

          <tr>
            <td colspan="6" align="right">
              <font size="2"></font>
            </td>
            <td colspan="" align="right">
              <font size="2"><b>Card 2%</b></font>
            </td>
            <td colspan="" align="right">
              <font size="2"><b><?php echo $_POST['cardpercamt']; ?></b></font>
            </td>
          </tr>


          <tr>
            <td colspan="6" align="right">
              <font size="2"></font>
            </td>
            <td colspan="" align="right">
              <font size="2">Net Payable:</font>
            </td>
            <td colspan="" align="right">
              <font size="2"><b> <?php echo "Rs." . $total; ?> </b></font>
            </td>
          </tr>


        <?php } ?>


        <tr>
          <td colspan="2" align="left">
            <font size="2"><b>Date: <?php echo $odate; ?></b></font>
          <td colspan="2" align="left">
            <font size="2"><b>Amount Paid : <?php echo "Rs." . $amt; ?> </b></font>
          </td>
          <?php $aa = (float)$total - (float)$amt;
          // echo $aa;
          ?>
          <td colspan="4" align="center">
            <font size="2"><b>Balance : <?php echo "Rs." . $aa; ?> </b></font>
          </td>
        </tr>
        <tr>
          <td colspan="8">
            <font size="2"><b>Payment By : <?php echo $pay_by; ?></b></font>
          </td>
        </tr>
        <tr>
          <td colspan="8">
            <font size="2"><b>Note : <?php echo $note; ?></b></font>
          </td>
        </tr>
      </table>
      </font>


      </td>
      </tr>
      <tr>
        <td>
          <hr />
          <table width="784" border="0">


            <tr>
              <td width="419" valign="top">
                <ul>
                  <li>
                    <font size="2">Subject to Mumbai jurisdiction</font> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>
                      <font size="2">E. & O . E</font>
                    </b>
                  </li>
                  <li>
                    <font size="2">Time 11 a.m. to 6 p.m.</font>
                  </li>
                </ul>
              </td>

              <td width="355" valign="top" align="right">
                <img src="shringaar.png" width="163" height="57" />
                <br /><br /><br />
                <font>Auth. Signatory</font>&nbsp;
              </td>
            </tr>
          </table>

        </td>
      </tr>
      </table>

    </div>
    <br /><br />
    <div style="font-size:12px; display:none;" id="pageNavPosition"></div>
    <center style="font-size:12px; display:none;"><a href="#"
        onclick='PrintDiv();'>Print</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a
        href="https://srishringarr.com/srishringarr/application/views/reports/approval.php">Back</a></center>

  </body>

  </html><?php  //echo $t1."-".$t2."-".$t3."-".$t4; 
}
if ($d > 0) {
  if ($t1 && $t2 && $t3 && $t4) {
    // 	mysqli_query($con,"COMMIT;");
    mysqli_commit($con);
    
    
    
    ?>
    
    <script>
        window.location.href="./sales_report_detail.php?id=<?php echo $thisBillID ; ?>" ; 
    </script>
    
    <?php 
    
    
  } else {

    // 		mysqli_query($con,"ROLLBACK;");
    mysqli_rollback($con);
    echo "<script> rollback(); </script>";
  }
} else
  // 	mysqli_query($con,"COMMIT;");
  mysqli_commit($con);

// $title = "Sales bill generated";
// include "nottest.php";

//Notification Code End


 } else {
        echo "Error: " . $con->error;
    }
    
            ?>
