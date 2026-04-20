# CyberEscape

CyberEscape is a randomly-generated cryptic puzzle game where players take on a time
challenge to complete all puzzles before the timer runs out. This game also includes AI enabled
features like a PHP puzzle randomizer, time coach, AI-confidence meter, and answer-proximity
score.

---

## File Structure & Functionality

### `/assets`

Contains static resources such as images, icons, or other supporting files used by the UI.

---

### `README.txt`

Documentation file (this file). Provides an overview of the project, features, and file purposes.

---

### `cyberbreakers.css`

* Main stylesheet for the entire project.
* Handles layout, animations, colors, and overall visual design.
* Provides the "cyber" themed UI.

---

### `cyberbreakers.html`

* Static landing or informational page for the game.
* Likely used as a front-facing introduction or splash screen.

---

### `index.php`

* **Login and entry point of the application**
* Handles:

  * User authentication
  * Session initialization
* Redirects authenticated users to the dashboard.

---

### `dashboard.php`

* Main hub after login.
* Displays:

  * Start game options
  * Possibly difficulty or mode selection
* Initializes game settings before redirecting to `game.php`.

---

### `game.php`

* **Core gameplay engine**
* Handles:

  * Puzzle generation (randomized each session)
  * Timer tracking via `$_SESSION`
  * Hint usage and penalties
  * Answer validation
  * Level progression
* Redirects to `results.php` upon win/loss.

---

### `results.php`

* Displays outcome of the game:

  * Win or failure
  * Time remaining
  * Hints used
  * Final score
* Triggers leaderboard updates if the player wins.

---

### `leaderboard.json`

* Stores leaderboard data in JSON format.
* Contains player scores, times, and rankings.

---

### `leaderboard.php`

* Reads from `leaderboard.json`
* Displays ranked player results
* Sorts players based on performance metrics.

---

### `logout.php`

* Destroys the current session.
* Logs the user out securely.
* Redirects back to login (`index.php`).

---

## Scoring System

Final score is based on:

* Remaining time
* Number of hints used (bonus for unused hints)

---

## AI Disclosure

Generative AI (ChaGPT and Claude Code) have been utilized in this project to help generate comments, identify bugs, and clean up our codebase. ALL code has been hand picked and reviewed by contributors to this project, and all work and logic ideas are our own.


