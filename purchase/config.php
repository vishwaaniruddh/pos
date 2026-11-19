<?php
/*$hostname='localhost'; //// specify host, i.e. 'localhost'
         $user='sarmicro_pos'; //// specify username
         $pass='Mypos1234'; //// specify password
         $dbase='sarmicro_srishringarr'; //// specify database name
         $connection = mysql_connect("$hostname" , "$user" , "$pass") 
or die ("Can't connect to MySQL");
         mysql_select_db($dbase , $connection) or die ("Can't select database.");*/
         date_default_timezone_set("Asia/Kolkata"); 
$con = mysqli_connect("localhost","sarmicro_pos","Mypos1234","sarmicro_srishringarr");
		 
		 ?>