function onload()
{	
  //  get_escalation();

}

function onchange()
{
	get_escalation();
	
}

function get_escalation()
{ debugger;
   var Atmid= $("#AtmID").val(); 
    var type= $("#type").val(); 
  if(Atmid==''){
	  swal("AtmID must required");
	  return false;
  }
    $.ajax({
        				url: "escalation_ajax.php", 
        				type: "GET",
        				data: {atmid:Atmid,type:type},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
                            

							$('#order-listing').dataTable().fnClearTable();
							$("#escalation_tbody").html('');
							$("#escalation_tbody").html(result);
							$('#order-listing').DataTable();
							
                           
                        })
                    });
}  
$("#type").change(function(){
				get_escalation();
			});
$("#AtmID").change(function(){
				get_escalation();
			});
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