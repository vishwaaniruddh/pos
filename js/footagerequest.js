$("#AtmID").change(function(){
	get_footage_list();
});
$("#Bank").change(function(){
	get_footage_list();
});
$("#Client").change(function(){
	get_footage_list();
});

function test(){
	 var Client= $("#Client").val(); 
	 var Bank= $("#Bank").val(); 
	 var AtmID= $("#AtmID").val(); 
    // AtmID = "P1DCHY03";
	  $.ajax({
				url: "api/dvrdashboard_alerts_ajax.php", 
				type: "POST",
				data: {atmid:AtmID,client:Client,bank:Bank,user_id:24},
				success: (function (result) { debugger;
				   var res = JSON.parse(result);
					console.log(res);
				})
			});
}


function get_footage_list()
{
	var Status= $("#status").val();
	var Client= $("#Client").val(); 
	var Bank= $("#Bank").val(); 
   var AtmID= $("#AtmID").val(); 
   $('#footagerequest_tbody').html('');
   $("#load").show();
 //  AtmID = "P1DCHY03";
   var user = "bank";
   if(Client=='')
    {
    	//swal("Oops!", "AtmID Must Required !", "error");
    	return false;
    }
    $.ajax({
        				url: "footage_request_ajax_list.php", 
        				type: "GET",
        				data: {atmid:AtmID,client:Client,bank:Bank,Status:Status,user:user},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
                            $('#order-listing').dataTable().fnClearTable();
                            $('#footagerequest_tbody').html('');
                            $('#footagerequest_tbody').html(result);
                            $('#order-listing').DataTable(
							{
							   "order": [[ 0, "desc" ]]
							}
							);
							$("#load").hide();
                        })
                    });
}   


function onchangeatmid() {
	var bank = $("#Bank").val();
	$.ajax({
		type: "GET",
		url: "getMasterData.php", 
		data: {bank:bank},
		dataType: "html",
		success: (function (result) {
			$("#AtmID").html('');
			$("#AtmID").html(result);
		})
	})
}
function onchangebank() { 
	var client = $("#Client").val();
	$.ajax({
		type: "GET",
		url: "getMasterData.php", 
		data: {client:client},
		dataType: "html",
		success: (function (result) {
			$("#Bank").html('');
			$("#Bank").html(result);
		})
	})
}	
