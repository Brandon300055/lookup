//search handles the live search for this plugin
function search(inputID, column) {
    // Declare variables
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById(inputID);
    filter = input.value.toUpperCase();
    table = document.getElementById("vehicle-table");
    tr = table.getElementsByTagName("tr");

    // Loop through all table rows, and hide those who don't match the search query
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[column];
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}