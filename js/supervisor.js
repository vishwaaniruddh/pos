function onload()
{
    get_clientwise();
	get_hourwise();
}

function onchange()
{
	get_clientwise();
	get_hourwise();
}

function get_clientwise()

{ debugger;

   var LoginID= $("#LoginID").val(); 
   LoginID='44';
  if(LoginID==''){
	  swal("LoginID must required");
	  return false;
  }
    $.ajax({
        				url: "supervisor_clientwise_ajax.php", 
        				type: "GET",
        				data: {loginid:LoginID},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
                            

							// $('#order-listing').dataTable().fnClearTable();
							$("#clientwise_tbody").html('');
							$("#clientwise_tbody").html(result);
							$('#order-listing3').DataTable(
								{
									buttons: [
										'copy', 'excel', 'pdf'
									]
								}
							);
							
                           
                        })
                    });
}  

function get_hourwise()

{ debugger;

   var LoginID= $("#LoginID").val(); 
   LoginID='44';
  if(LoginID==''){
	  swal("LoginID must required");
	  return false;
  }
    $.ajax({
        				url: "supervisor_hourwise_ajax.php", 
        				type: "GET",
        				data: {loginid:LoginID},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
							// $('#order-listing').dataTable().fnClearTable();
							$("#hourwise_tbody").html('');
							$("#hourwise_tbody").html(result);
							$('#order-listing2').DataTable();
							
                           
                        })
                    });
}  


