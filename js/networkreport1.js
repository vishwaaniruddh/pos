function onload()
{
   // get_ai_ticket();
}
$("#AtmID").change(function(){ debugger;
	get_Detail();
	get_view();
})
$("#Bank").change(function(){ debugger;
	//get_Detail();
	//get_view();
})
$("#Client").change(function(){ debugger;
	
})

        function get_Detail(){ debugger;
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
			$("#dvr_online_count").html(0);
			$("#dvr_offline_count").html(0);
			$("#router_online_count").html(0);
			$("#router_offline_count").html(0);
			$("#panel_online_count").html(0);
			$("#panel_offline_count").html(0);
			$("#load").show();
			$.ajax({
				url: "networkreport_count_ajax.php", 
				type: "POST",
				data: {client:Client,bank:Bank,atmid:AtmID},
				success: (function (result) { 
				   
				   debugger;
				
				   console.log(result);
				   var obj = JSON.parse(result);
				   var dvr_online_count = obj[0].dvr_online_count;
				   if(dvr_online_count>0){
					   var dvr_online_counthtml = '<a target="_blank" href="networkreport_details.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=0&device=D">'+dvr_online_count+'</a>';
					   $("#dvr_online_count").html(dvr_online_counthtml);
				   }else{
				      $("#dvr_online_count").html(dvr_online_count);
				   }
				   
				   
				   var dvr_offline_count = obj[0].dvr_offline_count;
				    if(dvr_offline_count>0){
					   var dvr_offline_counthtml = '<a target="_blank" href="networkreport_details.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=1&device=D">'+dvr_offline_count+'</a>';
					   $("#dvr_offline_count").html(dvr_offline_counthtml);
				   }else{
				      $("#dvr_offline_count").html(dvr_offline_count);
				   }
				   
				   var router_online_count = obj[0].router_online_count;
				   if(router_online_count>0){
					   var router_online_counthtml = '<a target="_blank" href="networkreport_details.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=0&device=R">'+router_online_count+'</a>';
					   $("#router_online_count").html(router_online_counthtml);
				   }else{
				      $("#router_online_count").html(router_online_count);
				   }
				   
				   var router_offline_count = obj[0].router_offline_count;
				   if(router_offline_count>0){
					   var router_offline_counthtml = '<a target="_blank" href="networkreport_details.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=1&device=R">'+router_offline_count+'</a>';
					   $("#router_offline_count").html(router_offline_counthtml);
				   }else{
				      $("#router_offline_count").html(router_offline_count);
				   }
				   
				   var panel_online_count = obj[0].panel_online_count;
				    if(panel_online_count>0){
					   var panel_online_counthtml = '<a target="_blank" href="networkreport_details.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=0&device=P">'+panel_online_count+'</a>';
					   $("#panel_online_count").html(panel_online_counthtml);
				   }else{
				      $("#panel_online_count").html(panel_online_count);
				   }
				   
				   var panel_offline_count = obj[0].panel_offline_count;
				   if(panel_offline_count>0){
					   var panel_offline_counthtml = '<a target="_blank" href="networkreport_details.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=1&device=P">'+panel_offline_count+'</a>';
					   $("#panel_offline_count").html(panel_offline_counthtml);
				   }else{
				      $("#panel_offline_count").html(panel_offline_count);
				   }
				  
				   
				})
		    });
		}  
		
		function get_view()
		{
		    var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			
			if(Client==''){
				swal("Oops!", "Bank Must Required !", "error");
				return false;
			}
			/*
			$('#ticketview_tbody').html('');
			$.ajax({
				url: "networkreport_table_ajax.php", 
				type: "GET",
				data: {client:Client,bank:Bank,atmid:AtmID},
				dataType: "html", 
				success: (function (result) { debugger;
				   console.log(result);
				 
				   $('#order-listing').dataTable().fnClearTable();

					
					$('#ticketview_tbody').html(result); 
					
					$('#order-listing').DataTable(
						{
						//	"order": [[ 0, "desc" ]]
                            dom: 'Bfrtip',
							buttons: [
								  'excelHtml5'
							]
						}
					);
					 $("#load").hide();
				})
			});  */
			
			$('#example').DataTable({
		
				 "bProcessing": true,
				 "serverSide": true,
				 "lengthMenu": [
				   [10, 25, 50, -1],
				   [10, 25, 50, "All"]
				],
				 "order": [[ 0, "desc" ]],
							"dom": 'Bfrtip',
						/*	"buttons": [
								  'excelHtml5'
							], */
						    "buttons": [
								{
									"extend": 'excel',
									"text": '<button class="btn"><i class="fa fa-file-excel-o" style="color: green;"></i>  Excel</button>',
									"titleAttr": 'Excel',
									"action": newexportaction
								},
							],
				 "ajax":{
					url :"networkreport_table_ajax1.php", // json datasource
					type: "post",  // type of method  ,GET/POST/DELETE
					data: {client:Client,bank:Bank,atmid:AtmID},
					error: function(){
					 // $("#employee_grid_processing").css("display","none");
					},
				 columns: [
				            { data: "atm_id" },
							{ data: "site_address" },
							{ data: "router_status" },
							{ data: "routerip" },
							{ data: "routerlast_communication" },
							{ data: "routeronlinepercent" },
							{ data: "dvr_status" },
							{ data: "dvrip" },
							{ data: "dvrlast_communication" },
							{ data: "dvronlinepercent" },
							{ data: "panel_status" },
							{ data: "panelip" },
							{ data: "panellast_communication" },
							{ data: "panelonlinepercent" }
						  ]	
				  }
				});  
				 $("#load").hide();
		}

function onchangeatmid() {
		var bank = $("#Bank").val();
		$("#AtmID").html('<option value="">Select</option>');
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {bank:bank},
			dataType: "html",
			success: (function (result) {
				
				$("#AtmID").html(result);
				get_Detail();
				get_view();
			})
		})
	}
function onchangebank() { debugger;
		var client = $("#Client").val();
		$("#Bank").html('');
		$("#AtmID").html('<option value="">Select</option>');
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {client:client},
			dataType: "html",
			success: (function (result) {
				
				$("#Bank").html(result);
				get_Detail();
				get_view();
			})
		})
	}	


