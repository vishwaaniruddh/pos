function onload()
{
   // get_ai_ticket();
}
function setNetworkStatus(onlinepercent,offlinepercent){
	  var c3PieChart = c3.generate({
            bindto: '#c3-pie-chart2',
            data: {
              // iris data from R
              columns: [
                ['offline (%)', offlinepercent],
                ['online (%)', onlinepercent],
              ],
              type: 'pie',
              onclick: function(d, i) {
                console.log("onclick", d, i);
              },
              onmouseover: function(d, i) {
                console.log("onmouseover", d, i);
              },
              onmouseout: function(d, i) {
                console.log("onmouseout", d, i);
              }
            },
            color: {
              pattern: ['#FF5E6D', '#FF7300'] 
            },
            padding: {
              top: 0,
              right: 0,
              bottom: 30,
              left: 0,
            }
          });

          setTimeout(function() {
            c3PieChart.load({
              columns: [
                ['offline (%)', offlinepercent],
                ['online (%)', onlinepercent],
              ]
            });
          }, 1500);

        /*  setTimeout(function() {
            c3PieChart.unload({
              ids: 'offline'
            });
            c3PieChart.unload({
              ids: 'online'
            });
          }, 2500); */
}

$("#Bank").change(function(){ 
	
})
$("#Client").change(function(){ 
	//getPanel_Detail();
	//getOnlinePercentMonthWise_Detail();
})
$("#show_detail").click(function(){ 
	getPanel_Detail();
	
})

        function getPanel_Detail(){
	        var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			//AtmID = "P1DCHY03";
			$("#dvr_online_count").html(0);
			$("#dvr_offline_count").html(0);
			$("#camera_online_count").html(0);
			$("#camera_offline_count").html(0);
			$("#hdd_fail_count").html(0);
			$('#c3-pie-chart2').html('');
			if(Client==''){
				swal("Oops!", "Client Must Required !", "error");
				return false;
			}
			
			/*if(AtmID==''){
				swal("Oops!", "AtmID Must Required !", "error");
				return false;
			}*/
			$("#load").show();
			
			$.ajax({
				url: "dvrhealth_dashboard_ajax.php", 
				type: "POST",
				data: {client:Client,bank:Bank,atmid:AtmID},
				success: (function (result) { debugger;
				 //  $("#load").hide();
				   
				   console.log(result);
				   var obj = JSON.parse(result);
				   var dvr_online_count = obj[0].dvr_online_count;
				   if(dvr_online_count>0){
					   var dvrcounthtml = '<a target="_blank" href="dvrstatuslist.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=0">'+dvr_online_count+'</a>';
					   $("#dvr_online_count").html(dvrcounthtml);
				   }else{
				       $("#dvr_online_count").html(dvr_online_count);
				   }
				   var dvr_offline_count = obj[0].dvr_offline_count;
				   if(dvr_offline_count>0){
					   var offdvrcounthtml = '<a target="_blank" href="dvrstatuslist.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=1">'+dvr_offline_count+'</a>';
					   $("#dvr_offline_count").html(offdvrcounthtml);
				   }else{
				       $("#dvr_offline_count").html(dvr_offline_count);
				   }
				  
				   var camera_working_count = obj[0].camera_working_count;
				   if(camera_working_count>0){
					   var onlinecameracounthtml = '<a target="_blank" href="camera_details.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=0">'+camera_working_count+'</a>';
					   $("#camera_online_count").html(onlinecameracounthtml);
				   }else{
				       $("#camera_online_count").html(camera_working_count);
				   }
				   
				   var camera_notworking_count = obj[0].camera_notworking_count;
				   if(camera_notworking_count>0){
					   var offlinecameracounthtml = '<a target="_blank" href="camera_details.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=1">'+camera_notworking_count+'</a>';
					   $("#camera_offline_count").html(offlinecameracounthtml);
				   }else{
				       $("#camera_offline_count").html(camera_notworking_count);
				   }
				   
				   var hdd_fail_count = obj[0].hdd_fail_count;
				   if(hdd_fail_count>0){
					   var offlinehddcounthtml = '<a target="_blank" href="hdd_details.php?client='+Client+'&bank='+Bank+'&atmid='+AtmID+'&status=1">'+hdd_fail_count+'</a>';
					   $("#hdd_fail_count").html(offlinehddcounthtml);
				   }else{
				       $("#hdd_fail_count").html(hdd_fail_count);
				   }
				  
				   
				   var onlinepercent = obj[0].total_online_percent;
				   var offlinepercent = obj[0].total_offline_percent;
				   setNetworkStatus(onlinepercent,offlinepercent);
				   /*
				   var dvr_condition = obj.login_status;
				   var hdd_condition = obj.hdd;
				   if(dvr_condition==0){
					   $("#dvr_online_count").html('1');
					   $("#dvr_offline_count").html('0');
				   }else{
					   $("#dvr_online_count").html('0');
					   $("#dvr_offline_count").html('1');
				   }
				   var totalcamera = 4;
				   var camera_working_count = 0;
				   if(obj.cam1=='working'){
					   camera_working_count = camera_working_count + 1;
				   }
				   if(obj.cam2=='working'){
					   camera_working_count = camera_working_count + 1;
				   }
				   if(obj.cam3=='working'){
					   camera_working_count = camera_working_count + 1;
				   }
				   if(obj.cam4=='working'){
					   camera_working_count = camera_working_count + 1;
				   }
				   var camera_notworking_count = totalcamera - camera_working_count;
				   $("#camera_online_count").html(camera_working_count);
				   $("#camera_offline_count").html(camera_notworking_count);
				   if(hdd_condition=='ok'){
					   $("#hdd_fail_count").html('0');
				   }else{
					   $("#hdd_fail_count").html('1');
				   }  */
				   
				   getOnlinePercentMonthWise_Detail();
				   
				})
		    });
		}  
		
		function getOnlinePercentDetail(){
			getPanel_Detail();
			getBarChartDetails();
			getOnlinePercentDateWise_Detail();
			getOnlinePercentMonthWise_Detail();
			
			
		}
		
		function getOnlinePercentMonthWise_Detail(){ debugger;
	        var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			var month = $("#cmbMonth").val(); 
			var year = $("#cmbYear").val();
			//AtmID = "P1DCHY03";
			$("#online_percent_table").html('');
			if(Client==''){
				swal("Oops!", "Client Must Required !", "error");
				return false;
			}
			
			/*if(AtmID==''){
				swal("Oops!", "AtmID Must Required !", "error");
				return false;
			}*/
			//$("#load").show();
			$("#online_percent_table").hide();
			$("#online_percent_table_load").show();
			$.ajax({
				url: "dvrhealth_dashboard_table_ajax.php", 
				type: "GET",
				data: {client:Client,bank:Bank,atmid:AtmID,month:month,year:year},
				dataType: "html", 
				success: (function (result) { debugger;
				  // $("#load").hide();
				   $("#online_percent_table").show();
			       $("#online_percent_table_load").hide();
				   $('#order-listing').dataTable().fnClearTable();
				   $('#online_percent_table').html('');
				   $("#online_percent_table").html(result);
				   $('#order-listing').DataTable(
						{
							"order": [[ 0, "desc" ]]
						}
					);
					
					getBarChartDetails();
				})
		    });
		}  
		
		function getOnlinePercentDateWise_Detail(){
	        var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			var month = $("#cmbMonth").val(); 
			var year = $("#cmbYear").val();
			//AtmID = "P1DCHY03";
			$("#siteonline_percent_table").html('');
			if(Client==''){
				swal("Oops!", "Client Must Required !", "error");
				return false;
			}
			
			/*if(AtmID==''){
				swal("Oops!", "AtmID Must Required !", "error");
				return false;
			}*/
			//$("#load").show();
			$.ajax({
				url: "dvrhealth_dashboard_sitetable_ajax.php", 
				type: "GET",
				data: {client:Client,bank:Bank,atmid:AtmID,month:month,year:year},
				dataType: "html", 
				success: (function (result) { 
				   $("#load").hide();
				   
                   $('#siteonline_percent_table').html('');
                   $("#siteonline_percent_table").html(result);
				  
				})
		    });
		} 
		
		function getBarChartDetails(){ 
			var Client = $("#Client").val();
			var Bank = $("#Bank").val();
			var AtmID = $("#AtmID").val();
			var month = $("#cmbMonth").val(); 
			var year = $("#cmbYear").val();
			$('#morris-bar-example').html('');
			if(Client==''){
				swal("Oops!", "Client Must Required !", "error");
				return false;
			}
			
			
			//$("#load").show();
			$.ajax({
				url: "dvrhealth_dashboard_barchart_table_ajax.php", 
				type: "POST",
				data: {client:Client,bank:Bank,atmid:AtmID,month:month,year:year},
				success: (function (result) { 
				   $("#load").hide();
				   
				
				   console.log(result);
				    var obj = JSON.parse(result);
					var res_data = obj[0].resdata;
					var dvronlinedata = obj[0].dvr_online_count;
					var dvrofflinedata = obj[0].dvr_offline_count;
					var dvronlinepercentdata = obj[0].online_percent;
					if ($("#morris-bar-example").length) {
						Morris.Bar({
						  element: 'morris-bar-example',
						  barColors: ['#63CF72', '#F36368', '#76C1FA', '#FABA66'],
						  data: res_data,
						  xkey: 'y',
						  ykeys: ['a', 'b'],
						  labels: ['Online', 'Offline']
						});
				  }
				  console.log(dvronlinedata);
				  console.log(dvronlinepercentdata);
				  var tbody_html = '<tr><td class="text-success"><b>Online</b></td>';
				  for(var i=0;i<dvronlinedata.length;i++){
					  tbody_html += '<td>'+dvronlinedata[i]+'</td>';
				  }
				  tbody_html += '</tr>';
				  tbody_html += '<tr><td class="text-danger">Offline</td>';
				  for(var i=0;i<dvrofflinedata.length;i++){
					  tbody_html += '<td>'+dvrofflinedata[i]+'</td>';
				  }
				  tbody_html += '</tr>';
				  tbody_html += '<tr><td>Status (%)</td>';
				  for(var i=0;i<dvronlinepercentdata.length;i++){
					  tbody_html += '<td>'+dvronlinepercentdata[i]+'</td>';
				  }
				  tbody_html += '</tr>';
				  $('#sites_online_percent_tbody').html(tbody_html);
				 // getOnlinePercentDateWise_Detail();
				})
			});
		}

function onchangeatmid() { debugger;
		var bank = $("#Bank").val();
		var client = $("#Client").val();
		$("#AtmID").html('');
		$("#AtmID").html('<option value="">Select</option>');
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {bankname:bank,clientname:client},
			dataType: "html",
			success: (function (result) { debugger;
				$("#AtmID").html('');
				$("#AtmID").html(result);
				//$("#load").show();
			//	getPanel_Detail();
	           
			})
		})
	}
function onchangebank() { 
		var client = $("#Client").val();
		$("#online_percent_table_load").show();
		$("#Bank").html('');
		$("#Bank").html('<option value="">Select</option>');
		$("#AtmID").html('');
		$("#AtmID").html('<option value="">Select</option>');
		$.ajax({
			type: "GET",
			url: "getMasterData.php", 
			data: {client:client},
			dataType: "html",
			success: (function (result) {
				$("#Bank").html('');
				$("#Bank").html(result);
			//	getPanel_Detail();
	            
			})
		})
	}	


