//this file manages the onclick event and prompt for the delete button
//when deleting a vehicle

function deletePrompt(showingItem, hiddenItem) {
    console.log(hiddenItem);

    var showing = document.getElementById(showingItem);
    var hidding = document.getElementById(hiddenItem);


    if (showing.style.display === "none") {
        showing.style.display = "block";
        hidding.style.display = "none";
    } else {
        showing.style.display = "none";
        hidding.style.display = "block";
    }
}