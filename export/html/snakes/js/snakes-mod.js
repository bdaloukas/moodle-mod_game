/*This code was originally based on code by
Husain Limdiyawala(MSc IT DA-IICT)*/

$(document).ready(function () {

});


//Global Variables
var totblocks = 0;
var data = "";
var currentblock = 0;
var position = 0;
var lastposition = new Array();
var randomno = 0;
var tots = new Array();
var l = 0;
var srcsnake = new Array(4);
var destsnake = new Array(4);

var ladsrc = new Array(3);
var laddest = new Array(3);
var quest = new Array(); //available questions along with multiple answers
var COR_answered = new Array(); //record all questions (along with answers) the user responded CORRECTLY
var WRO_answered = new Array(); //record all questions (along with answers) the user responded WRONGLY
var user = new Array();

//Constract table with questions and answers and pick question to display

quest[0] = "Spell 1";
quest[1] = "one";
quest[2] = "two";
quest[3] = "three";
quest[4] = "Spell 2";
quest[5] = "two";
quest[6] = "three";
quest[7] = "four";
quest[8] = "Spell 3";
quest[9] = "three";
quest[10] = "two";
quest[11] = "four";
quest[12] = "Spell 4";
quest[13] = "four";
quest[14] = "three";
quest[15] = "one";
z = 0;
for (z = 0; quest[z] != null; z++) {
    // Do nothing
}
allQuest = z / 4;

function selectQuest(all)
{
    pickOne = Math.floor((Math.random() * all));
    return pickOne;
}

//The Below Function will hide all the snakes

function hideAll() {
	document.getElementById("img1").style.display = "none";
	document.getElementById("img2").style.display = "none";
	document.getElementById("img3").style.display = "none";
	document.getElementById("img4").style.display = "none";

	document.getElementById("lad1").style.display = "none";
	document.getElementById("lad2").style.display = "none";
    document.getElementById("lad3").style.display = "none";
}

//The Below Function will Render The Main Board

function paintBoard(a) {
    totblocks = (a * a);
    if ((a * a) % 2 == 0) {
        currentblock = (a * a) - a + 1;
        for (j = 0; j < (a / 2); j++) {
            for (i = 0; i < a; i++) {
                data += "<div class='blocks' id='" + currentblock + "'>" + currentblock + "</div>";
                currentblock++;
            }
            currentblock -= (a + 1);

            for (i = 0; i < a; i++) {
                data += "<div id='" + currentblock + "' class ='blocks'>" + currentblock + "</div>";
                currentblock--;
            }
            currentblock -= (a - 1);
        }
    } else {
            currentblock = (a * a);
            for (j = 0; j < (a / 2); j++) {
                for (i = 0; i < a; i++) {
                    data += "<div class='blocks' id='" + currentblock + "'>" + currentblock + "</div>";
                    currentblock--;
                }

                currentblock -= (a - 1);

                if (currentblock < 2) {
                    break;
                }

                for (i = 0; i < a; i++) {
                    data += "<div id='" + currentblock + "' class ='blocks'>" + currentblock + "</div>";
                    currentblock++;
				}
                currentblock -= (a + 1);
		}
	}
    document.getElementById("cont").style.width = (a * 52 + 52) + "px"

	document.getElementById("cont").innerHTML = data;
	$("#cont").slideDown("slow");
	$("#cont").effect("shake",3000);
	$("img:hidden").fadeIn(5000);

	if (a == 6) {
	    registerSnake(158, 196, "img1", 14, 3, 0);
	    registerSnake(62, 183, "img2", 27, 24, 1);
	    registerSnake(175, 18, "img3", 18, 4, 2);
	    registerSnake(10, 45, "img4", 32, 23, 3);

	    registerLadder(27, 132, "lad1", 28, 34, 0);
	    registerLadder(90, 22, "lad2", 19, 30, 1);
	    registerLadder(179, 137, "lad3", 2, 16, 2);
    } else if (a == 8) {
        registerSnake(300, 380, "img1", 44, 29, 0);
        registerSnake(180, 550, "img2", 51, 46, 1);
        registerSnake(290, 50, "img3", 41, 40, 2);
        registerSnake(500, 280, "img4", 27, 22, 3);

        registerLadder(350, 515, "lad1", 19, 35, 0);
        registerLadder(180, 230, "lad2", 43, 54, 1);
        registerLadder(80, 350, "lad3", 53, 60, 2);
    }
}

//The below Function will simulate throwing of a dice
function throwDice(i) {
	randomno = Math.floor((Math.random() * 6)) + 1;
	document.getElementById("diceimg").src = "images/dice_" + randomno + ".PNG";
    document.getElementById("diceimg").style.display = "block";
	if (lastposition[i] > 0) {
        document.getElementById(lastposition[i]).style.background = "url(images/square52.png)";
	}
	tots[i] += randomno;

	if (totblocks - tots[i] >= 0) {
        lastposition[i] = tots[i];
        document.getElementById(tots[i]).style.background = "url(images/pawn1.png)";
	} else {
        tots[i] -= randomno;
        document.getElementById(tots[i]).style.background = "url(images/pawn1.png)";
	}
}

// The below Function Checks The Snake Biting for a user.
function snakescheck(k) {
	i = 0;

	for(i = 0; i <= srcsnake.length; i++) {
		if (srcsnake[i] == tots[k]) {
			alert("Ωχ! Σε τσίμπησε φίδι στο τετράγωνο " + srcsnake[i] + " και θα πρέπει να γυρίσεις στο τετράγωνο " + destsnake[i] + ", εκτός κι αν απαντήσεις σωστά στην ερώτηση που ακολουθεί.");
			document.getElementById(destsnake[i]).style.background = "url(images/pawn1.png)";
			document.getElementById(tots[k]).style.background = "url(images/square52.png)";
			lastposition[k] = destsnake[i];
			tots[k] = destsnake[i];
			break;
		}
	}

    if (!checkWin(k)) {
		alert("???d?se?!S???a??t???a!");
    }
}

//The below function checks the ladders for a user
function laddercheck(k) {
	i = 0;

	for(i = 0; i <= ladsrc.length; i++) {
		if (ladsrc[i] == tots[k]) {
            alert("Υπάρχει μια σκάλα στο τετράγωνο " + ladsrc[i] + " και θα σας οδηγήσει κατευθείαν στο τετράγωνο " + laddest[i] + "αν απαντήσεις σωστά στην ερώτηση που ακολουθεί.");
			document.getElementById(laddest[i]).style.background = "url(images/pawn1.png)";
			document.getElementById(tots[k]).style.background = "url(images/square52.png)";
			lastposition[k] = laddest[i];
			tots[k] = laddest[i];
			break;
		}
	}
	if(!checkWin(k)) {
		alert("You have won!");
    }
}

//The below Function checks for pythons

function pythoncheck(k) {
    i = 0;

	for (i = 0; i < pythons.length; i++) {

		if (pythons[i] == tots[k]) {
			alert("You have been eaten up by a python.Your game is over");
			document.getElementById(tots[k]).style.background = "url(images/csnake.gif) #000000";
			lastposition[k] = null;
			tots[k] = null;
			break;
		}
	}
}

// The below function will register a snake.
function registerSnake(tp, lft, dv, src, dest, i) {
	document.getElementById(dv).style.top = tp + "px";
	document.getElementById(dv).style.left = lft + "px";
	srcsnake[i] = src;
	destsnake[i] = dest;
}

// The below function will register a ladder..
function registerLadder(tp, lft, dv, src, dest, i) {
	document.getElementById(dv).style.top = tp + "px";
	document.getElementById(dv).style.left = lft + "px";
	ladsrc[i] = src;
	laddest[i] = dest;
}

//The below function checks the change in the boardtype selection combobox
function selectBoard() {
    totblocks = 0;
    data = "";
    currentblock = 0;
    position = 0;

    hideAll();
    if (document.getElementById("boardtype").value != null) {
        paintBoard(parseInt(document.getElementById("boardtype").value));
    }
}

// The below function checks the change in the player selection combobox.
function selectPlayer() {
    if (document.getElementById("players").value != null) {
        user[document.getElementById("players").value - 1] = 0;
		tots[document.getElementById("players").value - 1] = 0;
		lastposition[document.getElementById("players").value - 1] = 0;
		for(var j = 0; j < lastposition.length; j++) {
            lastposition[j] = 0;
            tots[j] = 0;
		}
	}
}

// The below function starts the play.
function play() {
	if (tots[l] != null) {
        disableField();
        document.getElementById("status").innerHTML = "<ul class='nodis'><li>O Paiktis " + (l + 1) + " </li><li>brisketai sto tetragwno " + tots[l] + "</li><li></li></ul>";

        Question();
        document.getElementById("status").innerHTML = "<ul class='nodis'><li>O Paiktis " + (l + 1) + " </li><li>vrisketai sto tetragwno " + tots[l] + "</li></ul>";
	} else {
        document.getElementById("status").innerHTML = "<ul class='nodis'><li>Molis exases...</li></ul>";
    }

    if( l == lastposition.length - 1) {
        l = 0;
    } else {
        l++;
    }
}

// The below function regulates the play.
function doit(i) {
    throwDice(i);
    if (checkWin(i)) {
        snakescheck(i);
		laddercheck(i);
    } else {
        alert("ÏëïêëÞñùóåò ôçí ðßóôá, óõã÷áñçôÞñéá!!!");
    }
}

// The below function checks whether the player has won or not.
function checkWin(i) {
	if( tots[i] == totblocks) {
        return false;
    } else {
        return true;
    }
}

// The below function will disable both the combobox .
function disableField() {
	document.getElementById("players").disabled = "disabled";
	document.getElementById("boardtype").disabled = "disabled";
}

function Question() {
    picked = selectQuest(allQuest);
    alert("Randomly selected number:" + picked);
    Q1 = prompt(quest[picked * 4], "Απάντηση");
    if (Q1 == quest[picked * 4 + 1]) {
        alert("Σωστά!")
        doit(l);

        COR_answered.concat(quest.splice(picked * 4, 4));
    } else {
        alert("Η απάντηση δεν ήταν σωστή. Χάνεις τη σειρά σου για αυτό το γύρο!")
        WRO_answered.concat(quest.splice(picked * 4, 4));
    }

    // Remove question and answers from available questions - (thus not allowing to have a Repeated question) ---XOXOXO
    allQuest--;
}
