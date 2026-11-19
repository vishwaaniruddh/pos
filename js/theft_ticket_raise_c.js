$("#AtmID").change(function(){
	theft_ticket();
	get_ticket_list();
});
$("#Bank").change(function(){
	theft_ticket();
	get_ticket_list();
});
$("#Client").change(function(){
	theft_ticket();
	get_ticket_list();
});


function theft_ticket(){
	 var Client= $("#Client").val(); 
	 var Bank= $("#Bank").val(); 
	 var AtmID= $("#AtmID").val(); 
    // AtmID = "P1DCHY03";
	 if(Client=='')
    {
    	//swal("Oops!", "AtmID Must Required !", "error");
    	return false;
    }
	  $.ajax({
				url: "theft_ticket_count.php", 
				type: "POST",
				data: {atmid:AtmID,client:Client,bank:Bank},
				success: (function (result) { debugger;
				   var res = JSON.parse(result);
					console.log(res);
					$('#theft_ticket_count').html(res[0].theft_count);
				})
			});
}

function get_ticket_list()
{
	//var Status= $("#status").val();
	var Status = "all";
	var Client= $("#Client").val(); 
	var Bank= $("#Bank").val(); 
   var AtmID= $("#AtmID").val(); 
   $('#theft_ticket_body').html('');
   $("#load").show();
 //  AtmID = "P1DCHY03";
   var user = "comfort";
   if(Client=='')
    {
    	//swal("Oops!", "AtmID Must Required !", "error");
    	return false;
    }
    $.ajax({
        				url: "theft_history_ajax_list.php", 
        				type: "GET",
        				data: {atmid:AtmID,client:Client,bank:Bank,user:user,Status:Status},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
                            $('#order-listing').dataTable().fnClearTable();
                            $('#theft_ticket_body').html('');
                            $('#theft_ticket_body').html(result);
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
