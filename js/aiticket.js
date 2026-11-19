function getTicketDetails()
{
    get_ai_ticket();
}
$("#portal").change(function(){ debugger;
	//get_ai_ticket();
});
$("#AtmID").change(function(){ debugger;
	//get_ai_ticket();
})
function get_ai_ticket()
{
   var Client= $("#Client").val();
   var Bank= $("#Bank").val();   
   var AtmCode= $("#AtmID").val(); 
   var start= $("#start").val(); 
   var end= $("#end").val(); 
   var portal = $("#portal").val();
  // AtmCode = "B1088910";
  if(Client==''){
	//  swal("Client must required");
	  return false;
  }
  $("#load").show();
    $.ajax({
        				url: "ai_ticket_view_ajax.php", 
        				type: "GET",
        				data: {client:Client,bank:Bank,atmid:AtmCode,start:start,end:end,portal:portal},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
                         /*  var obj = JSON.parse(result);
                           var atmcode = obj.ATMCode;
                            var aid = obj.aid;
                            var datetime = obj.DateTime;
                           aiticketview = "<tr> <td>" +atmcode+ "</td> <td></td> <td></td> <td></td>  <td> " +datetime+ " </td> <td></td> <td></td> <td> </td> <td> </td> <td> "+aid+" </td> <td> </td> </tr>";
                            */
							$('#order-listing').dataTable().fnClearTable();
							$("#aiticketview_tbody").html('');
							$("#aiticketview_tbody").html(result);
							//$('#order-listing').DataTable().ajax.reload(); 
								
							//    $('#order-listing').dataTable().fnDestroy();
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


