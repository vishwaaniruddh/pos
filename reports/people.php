<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>People</title>
<style>
input{ width:180px;}

.sub {width:100px;height:25px;}
</style>
</head>

<body>
<center>
    <a href="/pos/reports/custLst.php" style="font-size:18px;font-weight:bold;">Back</a>
<h1>New Customer </h1>
<form method="post" action="process_people.php">
<table>
<tr>
<td width="135" height="34">First Name :</td>
<td width="336"><input type="text" name="fn" id="fn" /></td>
</tr>

<tr>
<td height="34">Last Name :</td><td><input type="text" name="ln" id="ln" /></td>
</tr>

<tr>
<td height="34">Date of Birth :</td><td><input type="text" name="dob" id="dob" />(YYYY-MM-DD)</td>
</tr>

<tr>
<td height="34">Email :</td><td><input type="text" name="email" id="email" /></td>
</tr>

<tr>
<td height="34">Phone no. :</td><td><input type="text" name="phone" id="phone" /></td>
</tr>

<tr>
<td height="34">Address 1 :</td><td><input type="text" name="add1" id="add1" /></td>
</tr>

<tr>
<td height="34">Address 2 :</td><td><input type="text" name="add2" id="add2" /></td>
</tr>

<tr>
<td height="34">City :</td><td><input type="text" name="city" id="city" /></td>
</tr>

<tr>
<td height="34">State :</td><td><input type="text" name="state" id="state" /></td>
</tr>

<tr>
<td height="34">Country :</td><td><input type="text" name="coun" id="coun" /></td>
</tr>

<tr>
<td height="34">Zip :</td><td><input type="text" name="zip" id="zip" /></td>
</tr>

<tr>
<td>Comments :</td><td><textarea name="comm" id="comm" rows="3" cols="20" style="resize:none;"></textarea></td>
</tr>

<tr>
<td height="34">Accounts :</td><td><input type="text" name="acc" id="acc" /></td>
</tr>

<tr>
<td height="34">Taxable :</td><td><input type="checkbox" name="tax" id="tax" value="1" checked="checked"/></td>
</tr>

<tr>
<td height="34">
<input type="hidden"  name="mode" value="<?php echo $_GET['mode']; ?>" />
<input type="submit" name="submit" id="submit" class="sub" value="submit"/></td>

</tr>

</table>
</form>
</center>
</body>
</html>