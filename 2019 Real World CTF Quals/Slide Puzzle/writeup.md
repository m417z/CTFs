# Slide Puzzle

**Category:** Reverse \
**Score:** 126 (solved by 38 teams) \
**Original description:** RealWorld Slide Puzzle with modern cross-platform
expressive and flexible UI technology.

## Introduction

The given URL leads us to a classic [sliding
15-puzzle](https://en.wikipedia.org/wiki/Sliding_puzzle) with a nice material
UI. At the bottom we see an "Auto Play" checkbox, and counters for the amount of
moves and tiles left, all of which are not interesting for the challenge.

## Dive in

Looking at the source of the page, I saw a minimal HTML markup and a huge 1.5 MB
JavaScript file. I quickly deduced that the JS file originated from Dart code,
and the used framework is Flutter. The JS file is huge, minified and ugly, feels
a bit like looking at assembly code. I didn't find any "decompilers" for Dart or
Flutter, so I moved on.

## Fiddler

Trying to avoid the horror of the JS file, I moved to inspecting the HTTP
traffic. The first API that is being used at page load is:
`/api/new_game?width=4&height=4`, which returns the initial board state. To see
what happens next, I replaced the response with an almost-finished board, then
completed the game. What happened next is that I got the following message:
"Invalid answer, you must be cheating!". Fair enough, but now I know that the
request is sent to: `/api/verify`, and includes the moves I made.

## Solving the puzzle

To proceed further, it looks like I need to solve the puzzle. Looking at several
solvers around the internet, I finally used the
[15418Project](https://github.com/GuptaAnna/15418Project) project, which was
sufficiently fast and convenient. After solving the puzzle, I got a
"Congratulations!" response from the server, along with a large base64-encoded
binary blob. Mystery.

## Back to the UI

Next I replaced both API responses (`new_game` and `verify`) to see how the UI
proceeds once the puzzle is solved. What happens is that I'm asked to submit my
name, and once I do that, a high score table is displayed. So what do I do now?
Where's the flag? All I had is that huge mysterious data blob.

## The huge mysterious data blob

Next thing I wanted to figure out is where that data blob is being used. Since
the data blob came base64-encoded, I decided to put breakpoints on base64
decoding function candidates. I don't remember how I found the relevant
base64-decoding function, but it's the one with the "Missing padding character"
string inside it. In any case, once I stepped out of the base64-decoding
function, I landed on a function which ends with a condition and the string
"Congratulations again on you-know-what!". That was a strong hint that the
condition better be satisfied.

## The condition

I won't elaborate - you can look at the code - but the condition is satisfied
only if the name you submitted is the flag :) It boils down to a systems of
linear equations, which can be solved to get the desired name, which is the
flag. I used numpy to solve it.

## Code

A quick and dirty script can be found [here](solver.py).
