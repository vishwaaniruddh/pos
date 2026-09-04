 

    
    function states() {
//alert("hello");

var State=document.getElementById("State").value;
//alert(productname);
$.ajax({
                    
                    type:'POST',
                    url:'state_id.php',
                     data:'State='+State,
                     datatype:'json',
                    success:function(msg){
                        //alert(msg);
                       var jsr=JSON.parse(msg);
                       //alert(jsr.length);
                        var newoption=' <option value="">Select</option>' ;
                        $('#City').empty();
                        for(var i=0;i<jsr.length;i++)
                        {
                         
                       
                      //var newoption= '<option id='+ jsr[i]["ids"]+' value='+ jsr[i]["ids"]+'>'+jsr[i]["modelno"]+'</option> ';
		                   newoption+= '<option id="'+ jsr[i]["ids"]+'" value="'+ jsr[i]["stateid"]+'">'+jsr[i]["stateid"]+'</option> ';
			
                        
                        }                       
                     $('#City').append(newoption);
 
                    }
                })
                
            }

  var boolPnl="";
  function checkPanIP(){
	     var PanelsIP = document.getElementById("PanelsIP").value;  
     $.ajax({
                    
                    type:'POST',
                    url:'checkPanels_IP.php',
                     data:'PanelsIP='+PanelsIP, 
					 async: false,
                     success:function(msg){
                        //alert(msg);
                        if(msg>=1){
                            alert("Panels IP already exist");
                             boolPnl="0";
                        }else{
                            boolPnl="1";
                        }
                     }
  })
  
  if(boolPnl==1){
           //  alert("anans--"+boolemail)
            return true;
         }else{
             return false;
         }
 
  }
  
 
  var boolemail="";
  function checkip(){
      //alert("hello");
    var dv_ip = document.getElementById("DVRIP").value;  
     $.ajax({
                    
                    type:'POST',
                    url:'check_ip.php',
                     data:'dv_ip='+dv_ip, 
					 async: false,
                     success:function(msg){
                        //alert(msg);
                        if(msg>=1){
                            alert("DVR IP already exist");
                             boolemail="0";
                        }else{
                            boolemail="1";
                        }
                     }
  })
  
  if(boolemail==1){
           //  alert("anans--"+boolemail)
            return true;
         }else{
             return false;
         }
  }
  
  
  var bool="";
  function checkpanel(){
      //alert("hello");
    var NewPanelID = document.getElementById("NewPanelID").value;  
     $.ajax({
                    
                    type:'POST',
                    url:'check_panel.php',
                    data:'NewPanelID='+NewPanelID, 
					async: false,
                     success:function(msg){
                        //alert(msg);
                        if(msg>=1){
                            alert("NewPanel ID  already exist");
                             bool="0";
                        }else{
                            bool="1";
                        }
                     }
  })
  
  if(bool==1){
           //  alert("anans--"+boolemail)
            return true;
         }else{
             return false;
         }
  }
  
  var boolatm="";
  function checkAtm(){
      //alert("hello");
    var NewATMID = document.getElementById("ATMID").value;  
     $.ajax({
                    
                    type:'POST',
                    url:'check_Atm.php',
                    data:'NewATMID='+NewATMID, 
					async: false,
                     success:function(msg){
                        //alert(msg);
                        if(msg>=1){
                            alert("Atm Id already exist");
                             boolatm="0";
                        }else{
                            boolatm="1";
                        }
                     }
  })
  
  if(boolatm==1){
           //  alert("anans--"+boolemail)
            return true;
         }else{
             return false;
         }
  }
  
 
     function validation(){
         var a=confirm("are you sure want to submit ");
         if(a==1){
            alert("Site  added successfully");
            forms.submit();
         }else{
             alert("your form is not submited");
         }
     }
      
      function val(){
		 
		  var Customer = document.getElementById("Customer").value;
		  var Bank = document.getElementById("Bank").value;
		  var ATMID = document.getElementById("ATMID").value;
		  var Panel_Make = document.getElementById("Panel_Make").value; 
          var OldPanelID = document.getElementById("OldPanelID").value;
		  var Zone = document.getElementById("Zone").value;
          var DVRName = document.getElementById("DVRName").value;
		  var DVR_Model_num = document.getElementById("DVR_Model_num").value;
		  var Router_Model_num = document.getElementById("Router_Model_num").value;
		  
		  
		  var DVRIP = document.getElementById("DVRIP").value;
		  var Password = document.getElementById("Password").value;
	     
		 var State = document.getElementById("State").value;
		 var City = document.getElementById("City").value;
		 var engname = document.getElementById("engname").value;
       if (Customer== "")
	{
		// alert("Please select customer");
		 return false;
	}
	else if(Bank==""){
    //    alert("Please select Bank");
	 	return false; 
}
else
 if(ATMID==""){
       alert("please fill up atm id");
		return false; 
}
else if(Panel_Make==""){
       alert("please select Panel Make");
		return false; 
}
else if(OldPanelID==""){
       alert("please fill up Old Panel ID");
		return false; 
}
else if(Zone==""){
       alert("please Select Zone");
		return false; 
}
else if(DVRName==""){
    //    alert("please Select DVR Name");
		return false; 
}
else if(DVR_Model_num==""){
       alert("please fill up DVR Model Number");
		return false; 
}
else if(Router_Model_num==""){
       alert("please fill up Router Model Number");
		return false; 
}

else if(DVRIP==""){
       alert("please fill up DVR IP");
		return false; 
}
else if(Password==""){
       alert("please fill up Password");
		return false; 
}
else if(State==""){
    //    alert("please Select State");
	 	return false; 
}
else if(City==""){
    //    alert("please Select City");
	 	return false; 
}
else if(engname==""){
       alert("please fill up Engineer name");
		return false; 
}else{
	return true;
      }
      
	  }    
      
function finalval()
{
   
    if(checkAtm() && checkpanel() && checkPanIP() &&checkip()  )
    {
       return true; 
       
    }
    else
    {
        
        return false; 
        
    }
    
   
}

 

/*
$(document).ready(function(){
    $("#live").change(function(){
var a =document.getElementById('live').value;


if(a=="Y"){
    
$("#up").show();

}else{
    
     $("#up").hide();
     $("#up1").hide();
}
    });
});
*/

  function isNumberKey(evt) {
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode != 46 && charCode > 31
              && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }
		