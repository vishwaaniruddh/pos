<?php
ini_set("display_errors", 0);

$s = $_GET['submit'] ?? null;
$id = $_GET['cid'] ?? null;
$from = $_GET['from'] ?? null;
$to = $_GET['to_date'] ?? null;
$com_total = 0;
$pa = 0;
$ba = 0;
?>
<script type="text/javascript" src="datepick_js.js"></script>
<link rel="stylesheet" type="text/css" href="date_css.css" />

<script>
    function validate1(form1) {
        if (form1.invno === "") {
            if (form1.cid.value === -1) {
                alert("Please Select Customer Name to continue.");
                form1.cid.focus();
                return false;
            }
        }
        return true;
    }

    function loadPhoneNo() {
        var xmlhttp;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                var s = xmlhttp.responseText;
                var s1 = s.split('&&');
                if (s1[0] == "0") {
                    alert("No such Phone Number");
                } else {
                    document.getElementById("cid").value = s1[1];
                    MakeRequest();
                }
            }
        }
        var str = document.getElementById('phoneNo').value;
        xmlhttp.open("GET", "getbyphone.php?cid=" + str, true);
        xmlhttp.send();
    }
</script>

<div style="text-align: center;">
    <a href="/pos/home_dashboard.php">Back</a>
    <table width="1096" border="1" cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" align="center">
        <tr>
            <td align="center">
                <img src="bill.PNG" width="408" height="165" /><br /><br />
                <b>Rent Report</b>
            </td>
        </tr>
        <tr>
            <td width="1084" valign="top">

                <center>
                    <form action="rentReport.php" onSubmit="return validate1(this)">
                        <br />

                        <table width="1073" height="42">
                            <tr>
                                <td width="293" height="36"><strong>From Date :</strong>
                                    <input type="text" name="from" id="from" onClick="displayDatePicker('from');" />
                                </td>
                                <td width="216"><strong>To Date: </strong>
                                    <input type="text" name="to_date" id="to_date" onClick="displayDatePicker('to_date');" />
                                </td>
                                <td width="463" height="34">

                                    <strong> Phone Number:</strong> <input type="text" name="phoneNo" id="phoneNo" value="" /> <a href="#" onClick="loadPhoneNo();">Find</a> <br />

                                    <strong> Invoice Number:</strong> <input type="text" name="invno" id="invno" value="" />
                                    <br />
                                    <strong>Customer Name:&nbsp;</strong>&nbsp;&nbsp;
                                    <select name="cid" id="cid">
                                        <option value="-1">select</option>
                                        <?php
                                        include('../db_connection.php');
                                        $con = OpenSrishringarrCon();
                                        $result = mysqli_query($con, "SELECT * FROM  phppos_people order by first_name");
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='{$row['person_id']}'>{$row['first_name']} {$row['last_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td width="81"> <input type="submit" value="Search" name="submit" /></td>
                            </tr>
                        </table>
                    </form>
                </center>
            </td>
        </tr>

        <?php
        if (isset($s)) {

            if ($from == "" && $to == "") {

                $qry = "SELECT * FROM  `phppos_rent` where 1";

                if ($id != "-1") {
                    $qry .= " and cust_id='" . $id . "'";
                }
            } else if ($from == "") {
                $qry = "SELECT * FROM  `phppos_rent` where  bill_date=STR_TO_DATE('" . $to . "','%d/%m/%Y')";

                if ($id != "-1") {
                    $qry .= " and cust_id='" . $id . "'";
                }
            } else if ($to == "") {

                $qry = "SELECT * FROM  `phppos_rent` where  bill_date=STR_TO_DATE('" . $from . "','%d/%m/%Y')";

                if ($id != "-1") {
                    $qry .= " and cust_id='" . $id . "'";
                }
            } else {
                $qry = "SELECT * FROM  `phppos_rent` where  bill_date BETWEEN STR_TO_DATE('" . $from . "','%d/%m/%Y') and STR_TO_DATE('" . $to . "','%d/%m/%Y')";

                if ($id != "-1") {
                    $qry .= " and cust_id='" . $id . "'";
                }
            }

            if ($_GET["invno"] != "") {

                $qry .= " and (bill_id='" . $_GET["invno"] . "' or new_bill_number='".$_GET['invno']."')";
            }

// echo $qry ; 

            $res = mysqli_query($con, $qry);
            $num = mysqli_num_rows($res);
        ?>

            <tr>
                <td height="103">
                    <table border="1" cellpadding="4" cellspacing="0" width="1038" align="left">
                        <tr>
                            <th width='77' height="34"><U>Sr.No.</U></th>
                            <th width='77' height="34"><U>Bill No.</U></th>
                            <th width='221'><u>Customer Name</u></th>
                            <th width='89'><U>Bill Date</U></th>
                            <th width='102'><U>Commission</U></th>
                            <th width='144'><U> Total Commission</U></th>
                            <th width='142'><U>Rent Amount</U></th>

                            <th width='104'><u>Bill Detail</u></th>
                        </tr>
                        <?php
                        $i = 1;
                        while ($row = mysqli_fetch_assoc($res)) {

                            $new_bill_number = $row['new_bill_number'];
                            $sql1 = mysqli_query($con, "SELECT * FROM `phppos_people` WHERE `person_id`='" . $row['cust_id'] . "'");
                            $row1 = mysqli_fetch_assoc($sql1);

                            $qry2 = "SELECT sum(bal_amount) FROM  `phppos_rent` where bill_id ='" . $row['bill_id'] . "'";
                            $res2 = mysqli_query($con, $qry2);
                            $row2 = mysqli_fetch_row($res2);

                            $qry3 = "SELECT sum(`total_amount`) FROM `order_detail` WHERE bill_id ='" . $row['bill_id'] . "'";
                            $res3 = mysqli_query($con, $qry3);
                            $row3 = mysqli_fetch_row($res3);
                            $s = (float)$row3[0] + (float)$row['commission'] ;
                            // + $row['rent_amount'];

                        ?>
                            <tr>
                                <td width="77"><?php echo $i; ?></td>
                                <td width="" style="white-space:nowrap;"><?php echo $new_bill_number ? $new_bill_number : $row['bill_id']; ?></td>
                                <td width="221" align="center"><?php echo $row1['first_name'] . " " . $row1['last_name']; ?></td>
                                <td width="89"> <?php if (isset($row['bill_date']) && $row['bill_date'] != '0000-00-00') echo date('d/m/Y', strtotime($row['bill_date'])); ?></td>
                                <td width="102"><?php echo $row['commission_currency'] == 'Rs.' ? $row['commission_currency'] . "" . $row['commission'] : $row['commission'] . "" . $row['commission_currency']; ?></td>
                                <td width="144"><?php echo $row['total_commission'];
                                                $com_total += $row['total_commission']; ?></td>
                                <td width="142"><?php echo $s;
                                                $ba += $s; ?></td>
                                                
                                                
                                                
                                <td align="center" width="104"><a href="rent_report_detail.php?id=<?php echo $row['bill_id']; ?>" target="_new">Bill Detail</a> <br>
                                  
                                </td>
                            </tr>
                        <?php $i++;
                        } ?>
                        <tr>
                            <td colspan="6" align="right"><b>Total Commission Amount:</b></td>
                            <td width="144"><?php echo $com_total; ?></td>
                            <td width="142">&nbsp;</td>
                        </tr>
                        <tr>
                            <?php $sql14 = mysqli_query($con, "SELECT SUM( rent_amount)  FROM `phppos_rent`  WHERE `cust_id`='$id' and status='A'");
                            $row14 = mysqli_fetch_row($sql14); ?>
                            <td colspan="6" align="right"><b>Total Rent Amount :</b></td>
                            <td width="136"><?php echo $row14[0]; ?></td>
                            <?php //echo $s."/".$a."/".$row2[0]."<br/>"; ?>
                        </tr>
                        <tr>
                            <?php $sql4 = mysqli_query($con, "SELECT SUM( rent_amount)  FROM `phppos_rent`  WHERE `cust_id`='$id'");
                            $row4 = mysqli_fetch_row($sql4); ?>
                            <td colspan="6" align="right"><b><strong>Total Rent and Rent Return Amount</strong>:</b></td>
                            <td width="136"><?php echo $row4[0]; ?></td>
                            <?php //echo $s."/".$a."/".$row2[0]."<br/>"; ?>
                        </tr>
                        <tr>
                            
                            
                            <?php
                            
                            // echo "SELECT SUM(amount) FROM  `rent_amount`WHERE  `cust_id`='$id'" ; 
                            $sql5 = mysqli_query($con, "SELECT SUM(amount) FROM  `rent_amount`WHERE  `cust_id`='$id'");
                            $row5 = mysqli_fetch_row($sql5); ?>
                            <td colspan="6" align="right"><b>Total Paid Amount :</b></td>
                            <td width="136"><?php echo $row5[0]; ?></td>
                            <?php //echo $s."/".$a."/".$row2[0]."<br/>"; ?>
                        </tr>
                        <tr>
                            <td colspan="6" align="right"><b>Total Balance Amount :</b></td>
                            <td width="136"><?php echo $row4[0] - $row5[0] ?></td>
                            <?php //echo $s."/".$a."/".$row2[0]."<br/>"; ?>
                        </tr>
                    </table>
                </td>
            </tr>
        <?php } else { ?>
            <!-- Handle the case where $s is not set -->
        <?php } ?>
    </table>
</div>
<div align="center">You are using Point Of Sale Version 10.5.</div>