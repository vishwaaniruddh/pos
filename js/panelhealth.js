function onload()
{
  //  get_ticketview();
}
$("#portal").change(function(){ debugger;
	get_ticketview();
});
$("#AtmID").change(function(){

    get_ticketview();
});

function get_ticketview()
{  debugger;
   var Atmid= $("#AtmID").val(); 
   var Client= $("#Client").val();
    var Bank= $("#Bank").val(); 
	$('#ticketview_tbody').html('');
    if(Bank=='')
    {
    	//swal("Oops!", "AtmID Must Required !", "error");
    	return false;
    }
	if(Client=='')
    {
    	//swal("Oops!", "AtmID Must Required !", "error");
    	return false;
    }
    $.ajax({
        url: "panel_health_data.php", 
        type: "GET",
        data: {atmid:Atmid,bank:Bank,client:Client},
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

            $('#ticketview_tbody').html('');
            $('#ticketview_tbody').html(result); 
            
            
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
				//get_ticketview();
				$("#load").hide();
			})
		})
	}
function onchangebank() { 
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
			})
		})
	}	
