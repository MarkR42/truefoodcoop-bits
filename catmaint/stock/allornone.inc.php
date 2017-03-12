<?php
    # Javascript-driven "all or none" buttons.
?>
<button type="button" id="sel_all">All</button>
<button type="button" id="sel_none">None</button>


<script defer="defer">
/*
 * Make the "check all" and "check none" buttons work...
 */
function check_all(on)
{
    var cbs = document.querySelectorAll("input[type=checkbox]");
    for (var i=0; i< cbs.length; i++) {
        cbs[i].checked = on;
    }
}

document.getElementById("sel_all").addEventListener("click",
    function() { check_all(true); } );
document.getElementById("sel_none").addEventListener("click",
    function() { check_all(false); } );
    

</script>    
