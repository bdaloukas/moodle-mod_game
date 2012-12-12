Game module for Moodle
Copyright (C) 2004-2012  Vasilis Daloukas (http://bdaloukas.gr)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details:

http:www.gnu.org/copyleft/gpl.html

Game module for Moodle
===============================================================================
Created by:
      Vasilis Daloukas (bdaloukas)

Many ideas and code were taken from other Moodle modules and Moodle itself

Installation:
    * see http://docs.moodle.org/en/Installing_contributed_modules_or_plugins

This module gets input from quiz, glossary or questions and plays some games. The games are:

* hangman
* crossword
* cryptex
* millionaire
* sudoku
* Snakes and Ladders
* The hidden picture
* Book with questions

If you like the idea goto http://play2learn.gr/moodle for a demo in Moodle 1.9 or goto to http://ebusiness-lab.epdo.teimes.gr/play2learn/ for a demo in Moodle 2.

Currently works with 17 languages Català (ca), Deutsch (de), Ελληνικά (el), English (en), Español - Internacional (es), Euskara (eu), Français (fr), ית  (he), Hrvatski (hr), Italiano (it), Nederlands (nl), Norsk - bokmal ( no), Polski (pl), Português - Brasil (pt_br), Русский (ru), Shqip (sq), Українська (uk), 简体中文 (zh_cn) languages.

If you like the module Game please donate at http://bdaloukas.gr/donate/moodlegame/, help me to have more time to continue working on this project. This module is and will remain free, but your donation allows me to continue the development, and any amount is greatly appreciated.


Documentation http://docs.moodle.org/en/Game_module
Browse source code http://cvs.moodle.org/contrib/plugins/mod/game/
Changelog https://docs.google.com/document/pub?id=1-WJUa_Hbdo9eualKSjjP9wTnI4GVMs4JYDaLypU2IDQ
Discussion http://moodle.org/mod/forum/view.php?id=7220
Download for Moodle 2.0 http://download.moodle.org/download.php/plugins/mod/game.zip
Download for Moodle 1.9 http://download.moodle.org/download.php/plugins19/mod/game.zip
Bugs and Issues http://tracker.moodle.org/browse/CONTRIB/component/10295

Interface:

    * The interface is like a quiz. The student plays games and teacher can see the grades
    * You can set a text that will be visible at the bottom of the game. In this way will be a picture at the bottom and a crossword with questions about the picture
    * You can use pictures inside questions


Restrictions:

    * You can only backup/restore the data of game not the user attempts. (not works backup now)
    * In the report overview you can see only what students said for questions not for glossaryentries


Upgrade

    * Delete the files from mod/game
    * Copy the new files to mod/game
