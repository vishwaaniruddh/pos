function onload()
{
   // get_ai_ticket();
}
$("#AtmID").change(function(){ debugger;
	getPanel_Detail();
	get_ticketview();
})
$("#Bank").change(function(){ debugger;
	getPanel_Detail();
	get_ticketview();
})
$("#Client").change(function(){ debugger;
	
})

        function getPanel_Detail(){
	        var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			//AtmID = "P1DCHY03";
			if(Client==''){
				swal("Oops!", "Bank Must Required !", "error");
				return false;
			}
			/*if(AtmID==''){
				swal("Oops!", "AtmID Must Required !", "error");
				return false;
			}*/
			$("#load").show();
			$.ajax({
				url: "dash_board_ajax.php", 
				type: "POST",
				data: {client:Client,bank:Bank,atmid:AtmID},
				success: (function (result) { 
				   $("#load").hide();
				   debugger;
				
				   console.log(result);
				   var obj = JSON.parse(result);
				   var dvr_online_count = obj[0].dvr_online_count;
				   $("#dvr_online_count").html(dvr_online_count);
				   var camera_working_count = obj[0].camera_working_count;
				   $("#camera_working_count").html(camera_working_count);
				   var camera_notworking_count = obj[0].camera_notworking_count;
				   $("#camera_notworking_count").html(camera_notworking_count);
				   
				})
		    });
		}  
		
		function get_ticketview()
		{
		    var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			
			if(Client==''){
				swal("Oops!", "Bank Must Required !", "error");
				return false;
			}
			$("#load").show();
			$.ajax({
				url: "dash_board_table_ajax.php", 
				type: "GET",
				data: {client:Client,bank:Bank,atmid:AtmID},
				dataType: "html", 
				success: (function (result) { debugger;
				   console.log(result);
				 
				   $('#order-listing').dataTable().fnClearTable();

					$('#ticketview_tbody').html('');
					$('#ticketview_tbody').html(result); 
					
					
					//$('#order-listing').DataTable().ajax.reload(); 
						
					//    $('#order-listing').dataTable().fnDestroy();
					$('#order-listing').DataTable(
						{
						    "order": [[ 0, "desc" ]],
							dom: 'Bfrtip',
							  buttons: [
								  'excelHtml5'
							  ]
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
function onchangebank() { debugger;
		var client = $("#Client").val();
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {client:client},
			dataType: "html",
			success: (function (result) {
				$("#Bank").html('');
				$("#Bank").html(result);
				getPanel_Detail();
	            get_ticketview();
			})
		})
	}	


