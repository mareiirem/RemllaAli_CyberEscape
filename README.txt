
CyberEscape is a randomly-generated cryptic puzzle game where players take on a time
challenge to complete all puzzles before the timer runs out. This game also includes AI enabled
features like a PHP puzzle randomizer, time coach, AI-confidence meter, and answer-proximity
score.


Wireframe & UI Plan

1 index.php User login and
registration
portal.
Form POST
handling;
password_verify()
for security.
$_SESSION['user_id'] dashboard.php

2 dashboard.php Main hub;
displays high
scores and
game start.
File I/O to read
and display
leaderboard
data.
$_SESSION['username'] game.php,
logout.php

3 game.php The active
puzzle/code-
breaking
interface.
rand() for cipher
generation;
server-side timer
logic.
$_SESSION['correct_code'],
$_SESSION['start_time']
results.php
4 results.php Shows win/
loss outcome
and final
stats.
Conditional logic
to determine
success; score
calculation.
$_SESSION['final_score'],
$_SESSION['status']
dashboard.php

5 profile.php Displays
personal user
history and
stats.
Dynamic data
retrieval based
on current user
session.
$_SESSION['user_id'] dashboard.php

6 logout.php Clears all
data and logs
the user out.
session_unset()
and
session_destroy()
functions.
None (clears all active
variables)
index.php
