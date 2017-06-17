/* This code was originally based on code by
Husain Limdiyawala(MSc IT DA-IICT) */

// Global Variables.
var totblocks = 0;
var data = "";
var currentblock = 0;
var lastposition = new Array();
var randomno = 0;
var tots = new Array();
var l = 0;
var srcsnake = new Array(4);
var destsnake = new Array(4);

var ladsrc = new Array(3);
var laddest = new Array(3);
var quest = new Array(); // Available questions along with multiple answers.
var coranswered = new Array(); // Record all questions (along with answers) the user responded CORRECTLY.
var wroanswered = new Array(); // Record all questions (along with answers) the user responded WRONGLY.

// Constract table with questions and answers and pick question to display.

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

var z = 0;
var allQuest;

for (;;) {
    if (quest[z] === null) {
        break;
    }
}
allQuest = z / 4;

function selectQuest(all) {
    var pickone = Math.floor((Math.random() * all));

    return pickone;
}

// The below Function will simulate throwing of a dice.
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
    var i;

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

// The below function checks the ladders for a user.
function laddercheck(k) {
    var i;

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
    if (!checkWin(k)) {
        alert("You have won!");
    }
}

// The below function will register a snake.
function registerSnake(tp, lft, dv, src, dest, i) {
    document.getElementById(dv).style.top = tp + "px";
    document.getElementById(dv).style.left = lft + "px";
    srcsnake[i] = src;
    destsnake[i] = dest;
}

// The below function will register a ladder.
function registerLadder(tp, lft, dv, src, dest, i) {
    document.getElementById(dv).style.top = tp + "px";
    document.getElementById(dv).style.left = lft + "px";
    ladsrc[i] = src;
    laddest[i] = dest;
}

// The below function regulates the play.
function doit(i) {
    throwDice(i);
    if (checkWin(i)) {
        snakescheck(i);
        laddercheck(i);
    } else {
        alert("Congatulations!!!");
    }
}

// The below function checks whether the player has won or not.
function checkWin(i) {
    if (tots[i] == totblocks) {
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

function question() {
    var picked, q1;

    picked = selectQuest(allQuest);
    alert("Randomly selected number:" + picked);
    q1 = prompt(quest[picked * 4], "Απάντηση");
    if (q1 == quest[picked * 4 + 1]) {
        alert("Σωστά!");
        doit(l);

        coranswered.concat(quest.splice(picked * 4, 4));
    } else {
        alert("Η απάντηση δεν ήταν σωστή. Χάνεις τη σειρά σου για αυτό το γύρο!");
        wroanswered.concat(quest.splice(picked * 4, 4));
    }

    // Remove question and answers from available questions - (thus not allowing to have a Repeated question).
    allQuest--;
}
