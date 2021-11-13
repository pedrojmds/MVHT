var slider = document.getElementById("myRange");
var output = document.getElementById("value")


//changes the bar color to appear at the value selected
function handleEvent(e){
    var x = slider.value;
 
}
//applies the function above for the event of clicking as well as the event of moving the mouse
"click mousemove".split(" ").map(eventName => slider.addEventListener(eventName, handleEvent, false));



//Function to hide category elements;
function showhide(icon, ent1, ent2, sim = ''){
    var x = document.getElementById(String(ent1));
    var y = document.getElementById(String(ent2));
    var z = document.getElementById(String(sim));
    if (x.style.display === "none"){
        x.style.display = "block";
        y.style.display = "block";
        z.style.display = "block";
        document.getElementById(icon).src = "http://mvht.lasige.di.fc.ul.pt/iconfinder_icon-arrow-up-b_211623.png";
        

    } else{
        x.style.display = "none";
        y.style.display = "none";
        z.style.display = "none";

        document.getElementById(icon).src = "http://mvht.lasige.di.fc.ul.pt//iconfinder_icon-arrow-down-b_211614.png";
    }
}


function showhideDesc(ent1, ent2){
    var x = document.getElementById(String(ent1));
    var y = document.getElementById(String(ent2));

    if (x.style.display === "none"){
        x.style.display = "block";
        y.style.display = "block";
        document.getElementById('expandCollapse').src = "http://mvht.lasige.di.fc.ul.pt/iconfinder_icon-arrow-up-b_211623.png";

    } else{
        x.style.display = "none";
        y.style.display = "none";
        document.getElementById('expandCollapse').src = "http://mvht.lasige.di.fc.ul.pt/iconfinder_icon-arrow-down-b_211614.png";
    }
}

function hideEmptyElements(elem1, elem2, elemid){
    if(elem1 == 0 && elem2 == 0) {
        document.getElementById(elemid).style.display = "none";
    }

}

// when the user clicks on the bar it opens the popup
function showPopup() {
    var popup = document.getElementById("formSubmitButton");
    popup.style.visibility ="visible";
}
