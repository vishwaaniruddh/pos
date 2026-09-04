function onload()
{
   // get_queryticket();
}

$('#AtmID').change(function()
{
    get_queryticket(); 
});

function get_queryticket()
{
    var Atmid= $("#AtmID").val(); 
    var start= $("#start").val(); 
    var end= $("#end").val(); 
    
     if(Atmid=='')
     {
        // swal("Oops!", "PanelID Must Required !", "error");
         return false;
     }

        $.ajax({
                        url: "queryticket_ajax.php", 
        				type: "GET",
        				data: {atmid:Atmid,start:start,end:end},
						dataType: "html", 
        				success: (function (result) { debugger;
        				   console.log(result);
                        
							
							$('#order-listing').dataTable().fnClearTable();
							$("#queryticket_tbody").html('');
							$("#queryticket_tbody").html(result);
							$('#order-listing').DataTable({
                                "order": [[ 0, "desc" ]]
                            } );
                           
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