---
layout:     post
title:      "Testing Hubot Scripts"
excerpt:    Using Mocha, Sinon, Chai, and Nock, we can assemble a very reliable testing suite for custom hubot scripts.
date:       2015-08-10 15:31:06
categories:
tags:       testing, tdd, hubot, scripting, coffeescript
image:      /assets/articles/images/2015-07-06-testing-hubot-scripts/cover.jpg
---
{% include attributes.md %}

The Cloud Control Panel team uses a fork of [Github's Hubot][hubot] for a large variety of tasks.  Everything from our deployment pipeline to cross team communication is integrated with with our customized version of the IRC bot.

As the team's script codebase for Hubot reached the verge of 3000 lines, we began looking for a testing strategy.  The official Github repo didn't contain any sort of tests by default, so the team turned to third-party libraries.

Our initial interaction with testing came through the library [_](https://github.com).  It appeared to be very in depth, covering a wide range of different scenarios and script types.  However, I was deterred by the complexity of the tests.  Most of our team spends very little time writing CoffeeScript, so I wanted the tests scripts to be as straightforward as possible.

I turned to [mtsmfm](https://github.com/mtsmfm)'s [`hubot-test-helper`](http://github.com/mtsmfm/hubot-test-helper) library.  The library does an impressive job mocking out the basic functionality of the bot while still maintaining simplicity of the test cases.

The rest of this blog post covers some various testing methods as well as some pitfalls I ran across while building the test cases.  If you would prefer to go straight to the code repo, it is available on github with instructions on running the tests: [amussey/hubot-testing-boilerplate](https://github.com/amussey/hubot-testing-boilerplate)


## Basic message and response

For the following test, we'll be using part of the [`ping.coffee`][ping].

```coffeescript
module.exports = (robot) ->
  robot.respond /PING$/i, (msg) ->
    msg.send "PONG"
```

To test this piece of `ping.coffee`, we'll do the following:

```coffeescript
Helper = require('hubot-test-helper')
expect = require('chai').expect
sinon = require('sinon')

# helper loads a specific script if it's a file
helper = new Helper('./../scripts/ping.coffee')

describe 'ping', ->
  room = null

  beforeEach ->
    # Set up the room before running the test
    room = helper.createRoom()

  afterEach ->
    # Tear it down after the test to free up the listener.
    room.destroy()

  context 'user says ping to hubot', ->
    beforeEach ->
      room.user.say 'alice', 'hubot PING'
      room.user.say 'bob',   'hubot PING'

    it 'should reply pong to user', ->
      expect(room.messages).to.eql [
        ['alice', 'hubot PING']
        ['hubot', 'PONG']
        ['bob',   'hubot PING']
        ['hubot', 'PONG']
      ]
```

As it can be seen above, messages can be sent into the room using the `room.user.say` function.  The entire contents of the room can be read back using `room.messages`.  Each message is returned as a list, with the first element being the name of the user, and the second element being the actual message that they sent.

When run, the output of this test should appear as follows:

```

  ping
    user says ping to hubot
      ✓ should reply pong to user

```

The full [ping script can be found here][ping], and the full [ping test script can be found here][ping-test].


## Updating the Brain

To show interaction with the brain, we'll refer to the [`remember.coffee`][remember] script.  This script adds two commands to hubot: `hubot remember <text>` which stores a provided string in the brain, and `hubot memory`, which will recall that string.

```coffeescript
module.exports = (robot) ->
  robot.respond /remember (.*)$/i, (msg) ->
    robot.brain.data.memory = msg.match[1]
    msg.reply 'Okay, I\'ll remember that.'

  robot.respond /memory$/i, (msg) ->
    if not robot.brain.data.memory?
      robot.brain.data.memory = null

    if robot.brain.data.memory == null
      msg.reply 'I\'m not remembering anything.'
    else
      msg.reply robot.brain.data.memory
```

To test the contents of the brain, we can reach it by referencing `room.robot.brain.data.*`.  So, two simple tests could be written as follows:

```coffeescript
Helper = require('hubot-test-helper')
expect = require('chai').expect
sinon = require('sinon')
helper = new Helper('./../scripts/remember.coffee')

describe 'remember', ->
  room = null

  beforeEach ->
    room = helper.createRoom()

  afterEach ->
    room.destroy()

  context 'user asks Hubot for memory contents', ->
    beforeEach ->
      room.robot.brain.data.memory = 'brain contents'
      room.user.say 'mary', 'hubot memory'

    it 'should reply with the contents of the memory', ->
      expect(room.messages).to.eql [
        ['mary', 'hubot memory']
        ['hubot', '@mary brain contents']
      ]

  context 'user asks Hubot to remember something', ->
    beforeEach ->
      room.user.say 'jim', 'hubot remember this'

    it 'should have the memory set to "this"', ->
      expect(room.robot.brain.data.memory).to.eql 'this'
```

These tests will check two major things: that the correct brain key is being read, and that the value is being set in the correct brain key.

```

  remember
    user asks Hubot for memory contents
      ✓ should reply with the contents of the memory
    user sets memory and asks for memory contents
      ✓ should have the memory set to "this"

```

A more complete test suite for this script can be found [here][remember-test].



## Mocking an object



## Mocking the Request object

Occasionally, there will be methods off of Hubot's request object you'll want to mock in your own way.  One of the biggest functions I found myself wanting to mock was the `msg.random` function.  You can see


## Mock HTTP servers

In the event a script wants to communicate with an outside library,





## Common Pitfalls

I ran into a couple big problems while

If you are working on one test and another appears to randomly start failing, make sure to check that you are tearing things down correctly.


#### Environment booleans

If you pass a boolean through an environment variable (for example, in the case of the [`shipit.coffee` script][shipit]), keep in mind that the boolean value will be passed through as a string.  While attempting to write tests for [`shipit.coffee`][shipit-test], I ran into trouble setting `process.env.HUBOT_SHIP_EXTRA_SQUIRRELS` to `false`.  The code on lines 40 and 41 originally read:

```coffeescript
  if process.env.HUBOT_SHIP_EXTRA_SQUIRRELS
    regex = /ship(ping|z|s|ped)?\s*it/i
```

However, because environment variables are stored as strings, the variable the contained the value `'false'`.  The conditional would then see that, rather than the value being a boolean `false`, it existed as a string, therefore making `if 'false'` to be `true`.  To get around this, I was forced to change to code to read:

```coffeescript
  if process.env.HUBOT_SHIP_EXTRA_SQUIRRELS is 'true'
    regex = /ship(ping|z|s|ped)?\s*it/i
```

## Conclusion



[hubot]: [https://github.com/github/hubot]
[repo]: [https://github.com/amussey/hubot-testing-boilerplate/]
[boilerplate]: [https://github.com/amussey/hubot-testing-boilerplate/]
[ping]: [https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/ping.coffee]
[ping-test]: [https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/ping.coffee]
[pugme]: [https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/pugme.coffee]
[pugme-test]: [https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/pugme.coffee]
[shipit]: [https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/shipit.coffee]
[shipit-test]: [https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/shipit.coffee]
[remember]: [https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/remember.coffee]
[remember-test]: [https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/remember.coffee]
