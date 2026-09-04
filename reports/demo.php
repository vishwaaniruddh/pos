<?
// include('config.php');
    include('../db_connection.php') ;
$con=$conn=OpenSrishringarrCon();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$sql = "SELECT * FROM approval10thSept WHERE cust_id >0 ORDER BY cust_id, bill_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $previousRow = null;
    $currentCustId = null;

    while ($row = $result->fetch_assoc()) {
        $billId = $row['bill_id'];
        $custId = $row['cust_id'];
        $paidAmount = $row['paid_amount'];
        $amountTotal = $row['amountTotal'];

        // Check if we're processing a new customer
        if ($currentCustId !== $custId) {
            $previousRow = null;
            $currentCustId = $custId;
        }

        // Check if paid amount is greater than amountTotal
        if ($paidAmount > $amountTotal) {
            // Calculate remaining amount
            $remainingAmount = (float)$paidAmount - (float)$amountTotal;

            // Update current row with correct paid amount
           echo  $updateSql = "UPDATE approval10thSept SET paid_amount = $amountTotal WHERE bill_id = $billId AND cust_id = $custId";
            $conn->query($updateSql);

            // If there's a next row for the same customer, add remaining amount to it
            if ($previousRow !== null && $previousRow['cust_id'] === $custId) {
                $nextBillId = $previousRow['bill_id'];
                $nextPaidAmount = (float)$previousRow['paid_amount'] + (float)$remainingAmount;
                $updateSql = "UPDATE approval10thSept SET paid_amount = $nextPaidAmount WHERE bill_id = $nextBillId AND cust_id = $custId";
                $conn->query($updateSql);
            }
        } elseif ($paidAmount < $amountTotal) {
            // If there's a previous row for the same customer, add remaining amount to it
            if ($previousRow !== null && $previousRow['cust_id'] === $custId) {
                $nextBillId = $previousRow['bill_id'];
                $nextPaidAmount = (float)$previousRow['paid_amount'] + (float)$remainingAmount;
                $updateSql = "UPDATE approval10thSept SET paid_amount = $nextPaidAmount WHERE bill_id = $nextBillId AND cust_id = $custId";
                $conn->query($updateSql);
            }
        } else {
            // Both amounts are equal, shift to next row
        }

        // Store current row as previous row for the next iteration
        $previousRow = $row;
    }
} else {
    echo "No results found.";
}





return ; 
$id = '175713';

$allusersql = mysqli_query($con, "SELECT * FROM phppos_people WHERE person_id = '$id'");
if ($allusersql_result = mysqli_fetch_assoc($allusersql)) {
    $mobilenumber = $allusersql_result['phone_number'];
    
    // Get all person_ids with the matching mobile number
    $idsResult = mysqli_query($con, "SELECT GROUP_CONCAT(person_id) as person_ids FROM phppos_people WHERE phone_number = '$mobilenumber'");
    $idsRow = mysqli_fetch_assoc($idsResult);
    $userids = $idsRow['person_ids'];
    
    // Query to fetch records from phppos_rent with matching cust_id
    if (!empty($userids)) {
      echo   $qry = "SELECT * FROM phppos_rent WHERE cust_id IN ($userids) AND status = 'A'";
        $rentResult = mysqli_query($con, $qry);
        
        // Process the results
        while ($rentRow = mysqli_fetch_assoc($rentResult)) {
            // Output or process the rent data here
        }
    }
}
?>
