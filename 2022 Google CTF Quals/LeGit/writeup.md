# LeGit

**Category:** Misc \
**Score:** 201 (solved by 50 teams) \
**Original description:** I built this CLI for exploring git repositories. It's
still WIP but I find it pretty cool! What do you think about it?

## Introduction

What we have is a server running a Python script that allows to clone a git
repository and run several commands on it. The target repository is cloned into
the `/tmp/<encoded_repo_url>` folder. After cloning, the following commands are
available:

1. List files in repository (cd & ls)
2. Show file in repository (cat)
3. Check for updates (git fetch)
4. Pull updates (unimplemented)
5. Exit

## Command number 2

As with many challenges, the flag resides in the `/flag` file. Command number 2
caught my attention since it prints the contents of a file of choice, and if I'm
able to make it print the contents of the flag file, I win. Of course, it's not
that simple - the command verifies that the target file resides inside the
repository folder. Below is the command implementation:

```python
_REPO_DIR = "/tmp/<encoded_repo_url>"

# ...

def show_file():
  filepath = input(">>> Path of the file to display: ")
  real_filepath = os.path.realpath(os.path.join(_REPO_DIR, filepath))
  if _REPO_DIR != os.path.commonpath((_REPO_DIR, real_filepath)):
    print("Hacker detected!")
    return
  result = subprocess.run(["cat", real_filepath], capture_output=True)
  if result.returncode != 0:
    print("Error while retrieving file content.")
    return
  print(result.stdout.decode())
```

The author considered the possibility that the target file could be a symbolic
link, and used `os.path.realpath` to resolve symlinks before verifying that the
target file is inside the repository folder. If I could only fool
`os.path.realpath` and make it return a symlink without resolving it...

## A failed attempt: Invalid UTF-8 characters

Paths in Linux [may contain invalid UTF-8
characters](https://unix.stackexchange.com/questions/667652/can-a-file-path-be-invalid-utf-8).
For example, try `mkdir ''$'\334''berraschung'`. I hoped that it would confuse
Python which by default uses UTF-8 for strings, but it worked correctly until I
accessed the string with the path. Looks like path operations worked correctly,
but trying to access the resulting string raised the `UnicodeDecodeError`
exception.

## Analyzing `os.path.realpath`

Next thing I did was looking at the Python implementation of `os.path.realpath`.
The implementation uses the `lstat` function to check whether a file is a
symlink. If `lstat` fails, Python assumes that the file isn't a symlink, so I
checked what could cause `lstat` to fail. One of the possible error conditions
is `ENAMETOOLONG` which is returned when the path is too long. I didn't find
other error conditions that looked relevant, so I focused on long paths.

Note: Python 3.10 adds the `strict` parameter to `os.path.realpath`, which is
`False` by default. When `strict` is set to `True`, `os.path.realpath` fails if
`lstat` returns an error. My solution to the challenge wouldn't work with this
parameter set to `True`.

## Abusing long paths

Typically Linux has a maximum filename length of 255 characters, and paths that
are passed to syscalls can't exceed 4095 characters
([reference](https://unix.stackexchange.com/questions/596653/nested-directory-depth-limit-in-ext4)).

So I tried creating a symlink in my repository with a long path, like this:

> `/tmp/repo/<many_characters>/lnk`

The idea was that the path would be longer than 4095 characters, `lstat` would
fail and `os.path.realpath` would return the path as-is. That worked, but `cat`
failed with the same path for the same reason - it was too long - so I had to
think of a better idea. Since `os.path.realpath`, upon the failure of `lstat`,
keeps traversing the path, and since I needed a shorter path, I tried adding
`..` directories to shorten the result:

> `/tmp/repo/<many_characters>/lnk/../../..`

It indeed shortened the path, but I needed the result to be both a symlink and a
path that's not too long, and if it would land on a symlink, it would follow it.
Catch 22.

Finally, I found the following solution: If the link was traversed in the past,
Python assumes it's a symlink loop and gives up. So I made sure to start with a
symlink that would make Python think there's a loop and return it:

> `/tmp/repo/flag` → points to `<many_characters>/lnk/../../flag`

> `/tmp/repo/<many_characters>/lnk` → points to `/`

In this case, `os.path.realpath('/tmp/repo/flag')` tries to resolve
`/tmp/repo/<many_characters>/lnk` with `lstat` which fails with `ENAMETOOLONG`,
and keeps on traversing the path, finally getting back to `/tmp/repo/flag` and
returning it. Then, `/tmp/repo/flag` is passed to `cat` which resolves
`<many_characters>/lnk` (note that this time it's shorter than 4095 characters
since it's a relative path) to `/`, and finally traversing to `/flag` and
printing it.

## Solution script

A script implementing the solution can be found [here](sol.py).
