/**
 * Randomiser script
 */
const queryString = window.location.search;
const urlParams = new URLSearchParams(queryString);
let token = urlParams.get('token');
let names = urlParams.get('names');
let emoji_support = urlParams.get('emojis');
let html_format = urlParams.get('html');
let text = urlParams.get('text');
let list = [];

if ( names ) {
    list = names.split(",");
}

function shuffle(array) {
    let currentIndex = array.length,  randomIndex;
  
    // While there remain elements to shuffle.
    while (currentIndex != 0) {
  
      randomIndex = Math.floor(Math.random() * currentIndex);
      currentIndex--;  
      [array[currentIndex], array[randomIndex]] = [array[randomIndex], array[currentIndex]];
    }
  
    return array;
}

function shuffleInput() {
    if (list) {
        const weekday = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const d = new Date();
        let day = weekday[d.getDay()];
        let month = months[d.getMonth()];
        document.write("Good Morning Team, Today is " + day + ', ' + d.getDay() + ' ' + month + ' ' + d.getFullYear()) + "\n";
        list = shuffle(list);
        list.forEach((element) => {
            //console.log(element);
            document.write("\n");
            document.write("<br>");
            document.write("&rsaquo; ");
            document.write(element);
        });
    }
}
