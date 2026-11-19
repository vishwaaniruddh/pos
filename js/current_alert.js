get_ai_ticket();
$("#portal").change(function(){ debugger;
	get_ai_ticket();
});
$("#AtmID").change(function(){ debugger;
	get_ai_ticket();
})
function get_ai_ticket()
{
   var Client= $("#Client").val();
   var Bank= $("#Bank").val();   
   var AtmCode= $("#AtmID").val(); 
   var start= $("#start").val(); 
   var end= $("#end").val(); 
   var portal = $("#portal").val();
  
  $("#load").show();
    $.ajax({
        				url: "current_alert_ajax.php", 
        				type: "GET",
        				data: {client:Client,bank:Bank,atmid:AtmCode,start:start,end:end,portal:portal},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
                         
							$('#order-listing').dataTable().fnClearTable();
							$("#aiticketview_tbody").html('');
							$("#aiticketview_tbody").html(result);
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
		$("#load").show();
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {bank:bank},
			dataType: "html",
			success: (function (result) {
				$("#AtmID").html('');
				$("#AtmID").html(result);
				$("#load").hide();
				//get_ai_ticket();
			})
		})
	}
function onchangebank() { debugger;
		var client = $("#Client").val();
		$("#load").show();
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {client:client},
			dataType: "html",
			success: (function (result) {
				$("#Bank").html('');
				$("#Bank").html(result);
				$("#load").hide();
				//get_ai_ticket();
			})
		})
	}	


