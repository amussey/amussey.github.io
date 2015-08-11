---
layout:     post
title:      "Testing Hubot Scripts"
excerpt:    Using Mocha, Sinon, Chai, and Nock, we can assemble a very reliable testing suite for custom Hubot scripts.
date:       2015-08-11 16:31:06
categories:
tags:       testing, tdd, hubot, scripting, coffeescript
image:      /assets/articles/images/2015-08-11-testing-hubot-scripts/cover.jpg
---
{% include attributes.md %}

The Cloud Control Panel team uses a fork of [Github's Hubot][hubot] for a large variety of tasks.  Everything from our deployment pipeline to cross team communication is integrated with with our customized version of the IRC bot.

As the team's script codebase for Hubot verged on 3000 lines of code, we began looking for a testing strategy.  The official Github repo didn't contain any sort of tests by default, so the team turned to third-party libraries.

Our initial interaction with testing came through the library [hubot-mock-adapter](https://github.com/blalor/hubot-mock-adapter).  It appeared to be very in depth, covering a wide range of different scenarios and script types.  However, I was deterred by the complexity of the tests.  Most of our team spends very little time writing CoffeeScript, so I wanted the tests scripts to be as straightforward as possible.

I next turned to [mtsmfm](https://github.com/mtsmfm)'s [`hubot-test-helper`](http://github.com/mtsmfm/hubot-test-helper) library.  The library does an impressive job mocking out the basic functionality of the bot while still maintaining simplicity in the test cases.

The rest of this blog post covers some various testing cases as well as a couple pitfalls I ran across while building the tests.  If you would prefer to go straight to the code repo, it is available on Github with instructions on running the tests: [amussey/hubot-testing-boilerplate](https://github.com/amussey/hubot-testing-boilerplate)


## Basic message and response

This is about as simple as it gets: a user says one thing, Hubot replies with another.  For the following test, we'll be using part of the [`ping.coffee`][ping] script.

```coffeescript
module.exports = (robot) ->
  robot.respond /PING$/i, (msg) ->
    msg.send "PONG"
```

To test this, we'll do the following:

```coffeescript
Helper = require('hubot-test-helper')
expect = require('chai').expect

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


## Private Messages

Inside of [`secret.coffee`][secret], we can see a script with Hubot replying over private message.

```coffeescript
module.exports = (robot) ->
  robot.respond /tell me a secret$/i, (msg) ->
    msg.sendPrivate 'whisper whisper whisper'
```

We can test that this private message was transmitted in the following way:

```coffeescript
Helper = require('hubot-test-helper')
expect = require('chai').expect
helper = new Helper('./../scripts/secret.coffee')

describe 'secret', ->
  room = null

  beforeEach ->
    room = helper.createRoom()

  afterEach ->
    room.destroy()

  context 'user asks hubot for a secret', ->
    beforeEach ->
      room.user.say 'alice', '@hubot tell me a secret'

    it 'should not post to the public channel', ->
      expect(room.messages).to.eql [
        ['alice', '@hubot tell me a secret']
      ]

    it 'should private message user', ->
      expect(room.privateMessages).to.eql {
        'alice': [
          ['hubot', 'whisper whisper whisper']
        ]
      }
```

The naming on the two tests makes them relatively self-explanatory: a check to make sure that Hubot sent back the expected message (through `room.privateMessages`), and a check to make sure that Hubot did not post anything to the public channel.  If it's not apparent in the test above, Hubot stores all of the private messages it sends in `room.privateMessages`.  The array keeps track of each message keyed by the username that the message was sent to.  In this case, we only have the one key (`alice`), since she was the only one to receive a message.

The [`secret.coffee` script][secret] and the [`secret.coffee` test script][secret-test] are available on Github [here][secret] and [here][secret-test], respectively.

```

  private-message
    user asks hubot for a secret
      ✓ should not post to the public channel
      ✓ should private message user

```

## Updating the Brain

To show interaction with the brain, we'll refer to the [`remember.coffee` script][remember].  This script adds two commands to Hubot: `hubot remember <text>` which stores a provided string in the brain, and `hubot memory`, which will recall that string.

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

These tests will check that the correct brain key is being read, and that the value is being set in the correct brain key.

```

  remember
    user asks Hubot for memory contents
      ✓ should reply with the contents of the memory
    user sets memory and asks for memory contents
      ✓ should have the memory set to "this"

```

A more complete test suite for this script can be found [here][remember-test].


## Stubbing an object

When using third party libraries in a script, we will want to test the functionality of the script without relying on the third party libraries.

A quick and dirty example is set up below using the [Moment.js](http://momentjs.com/) library.

```coffeescript
moment = require('moment')

module.exports = (robot) ->
  robot.respond /convert (.*)$/i, (msg) ->
    msg.send moment.unix(msg.match[1]).toString()
```

To test this, we'll want to mock out both the `moment.unix()` call (which creates a `moment` object at the input timestamp) and the `moment.unix().toString()` call (which returns the `moment` object in the form of a text string).  That way, regardless of changes to the library (or the timezone of the user), the function will output with consistency.

```coffeescript
Helper = require('hubot-test-helper')
expect = require('chai').expect
assert = require('chai').assert
sinon = require('sinon')

# helper loads a specific script if it's a file
helper = new Helper('./../scripts/timestamp.coffee')

describe 'timestamp', ->
  room = null
  moment = null
  momentUnixStub = null
  momentUnixToStringStub = null

  beforeEach ->
    moment = require('moment')
    momentUnixToStringStub = sinon.stub()
    momentUnixToStringStub.returns("Sun Oct 16 2011 16:17:56 GMT+0000")
    momentUnixStub = sinon.stub moment, "unix", () ->
      return {toString: momentUnixToStringStub}

    room = helper.createRoom()

  afterEach ->
    moment.unix.restore()
    room.destroy()

  context 'user asks hubot to convert', ->
    beforeEach ->
      room.user.say 'jim', 'hubot convert 1318781876'

    it 'should echo message back', ->
      expect(room.messages).to.eql [
        ['jim', 'hubot convert 1318781876']
        ['hubot', 'Sun Oct 16 2011 16:17:56 GMT+0000']
      ]

    it 'should have called toString', ->
      expect(momentUnixToStringStub.callCount).to.eql 1

    it 'should have called unix() with the correct parameters', ->
      expect(momentUnixStub.args[0]).to.eql [ '1318781876' ]
```

Inside the `beforeEach` block, we create two stubs: one for `moment.unix`, the other for `moment.unix.toString`.  The generated stubs are stored so they can be tested against, be it for call count or the parameters with which they were called.

```

  timestamp
    user asks hubot to convert
      ✓ should echo message back
      ✓ should have called toString
      ✓ should have called unix() with the correct parameters

```

The [timestamp.coffee script][timestamp] can be found on Github [here][timestamp], and the [timestamp.coffee test script][timestamp-test] can be found on Github [here][timestamp-test].


## Mocking the Request object

Occasionally, there will be methods off of Hubot's request object you'll want to mock out.  One of the biggest functions I found myself wanting to mock was the `msg.random` function.  You can see a simplified version of the built in script, [shipit.coffee][shipit], using the `msg.random` function below:


```coffeescript
squirrels = [
  "https://img.skitch.com/20111026-r2wsngtu4jftwxmsytdke6arwd.png",
  "http://i.imgur.com/DPVM1.png",
  "https://dl.dropboxusercontent.com/u/602885/github/squirrelmobster.jpeg",
]

module.exports = (robot) ->
  robot.hear /ship\s*it/i, (msg) ->
    msg.send msg.random squirrels
```

In order to predictably test that a squirrel is being output, we need to set the `msg.random` to output something consistant.  We can do this by mocking out the request object on the test Hubot.

```coffeescript
Helper = require('hubot-test-helper')
expect = require('chai').expect
helper = new Helper('./../scripts/shipit.coffee')

class MockResponse extends Helper.Response
  random: (items) ->
    "http://i.imgur.com/DPVM1.png"


describe 'shipit', ->
  room = null

  beforeEach ->
    room = helper.createRoom({'response': MockResponse})

  afterEach ->
    room.destroy()

  context 'user says "ship it"', ->
    beforeEach ->
      room.user.say 'alice', 'ship it'

    it 'should respond with an image', ->
      expect(room.messages[1]).to.eql ['hubot', 'http://i.imgur.com/DPVM1.png']

```

By extending `Helper.Response` into `MockResponse` and redefining the `random` function, we can ensure consistent output of `random` while still maintaining the functionality of the rest of `Responses` functions.  This custom `Response` object can then be pushed into our test Hubot when creating the room (`room = helper.createRoom({'response': MockResponse})`).

The rest of the test script for [`shipit.coffee`][shipit] can be found [here][shipit-test].

```

  shipit
    user says "ship it"
      ✓ should respond with an image

```


## Mock HTTP servers

In the event a script wants to communicate with the outside world, we'll have to put an HTTP listener in place in order to mock out that communication.  A simple example is the `pug me` script, [`pugme.coffee`][pugme]:


```coffeescript
module.exports = (robot) ->

  robot.respond /pug me/i, (msg) ->
    msg.http("http://pugme.herokuapp.com/random")
      .get() (err, res, body) ->
        msg.send JSON.parse(body).pug
```

We can mock out the HTTP server on the other end of that request using the following test:

```coffeescript
Helper = require('hubot-test-helper')
expect = require('chai').expect
nock = require('nock')

helper = new Helper('./../scripts/pugme.coffee')

describe 'pugme', ->
  room = null

  beforeEach ->
    room = helper.createRoom()
    do nock.disableNetConnect
    nock('http://pugme.herokuapp.com')
      .get('/random')
      .reply 200, { pug: 'http://imgur.com/pug.png' }

  afterEach ->
    room.destroy()
    nock.cleanAll()

  context 'user asks hubot for a pug', ->
    beforeEach (done) ->
      room.user.say 'alice', 'hubot pug me'
      setTimeout done, 100

    it 'should respond with a pug url', ->
      expect(room.messages).to.eql [
        [ 'alice', 'hubot pug me' ]
        [ 'hubot', 'http://imgur.com/pug.png' ]
      ]
```

In this case, the `beforeEach` function has a callback function `done` that will be called after the timeout within the before each is done.  The `setTimeout done, 100` will cause the beforeEach to pause for 100 ms before continuing with the tests.  This will give the mock HTTP responder (and subsequently Hubot) adequate time to respond before the test assertion is run<sup>1</sup>.

The `nock` HTTP listeners can also be chained to define multiple endpoints.  For example:

```coffeescript

  beforeEach ->
    room = helper.createRoom()
    do nock.disableNetConnect
    nock('http://pugme.herokuapp.com')
      .get('/random')
      .reply 200, { pug: 'http://imgur.com/pug.png' }
      .get('/bomb?count=5')
      .reply 200, { pugs: ['http://imgur.com/pug1.png', 'http://imgur.com/pug2.png'] }
      .get('/count')
      .reply 200, { pug_count: 365 }

```

Another important piece to note here are the `nock` listeners being torn down in the `afterEach` block (`nock.cleanAll()`).  Not doing so can result in some odd, unpredictable results.

The complete [`pugme.coffee`][pugme] script can be referenced [here][pugme], and the complete test suite can be found [here][pugme-test].

```

  pugme
    user asks hubot for a pug
      ✓ should respond with a pug url

```


## Common Pitfalls

### Tearing Down Mocks

As noted in the section about **Mock HTTP servers**, make sure that mocks are always torn town.  If you are working on one test and another randomly start failing, make sure that you are tearing things down correctly in the prior tests.


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

I hope that this blog post, along with the contents of the [hubot-testing-boilerplate repo](https://github.com/amussey/hubot-testing-boilerplate), help you to build out your test suite.  The above code is by no means perfect, so pull requests are more than welcome.  If you have any additional questions, please leave them in the comment section below!


[hubot]: https://github.com/github/hubot
[repo]: https://github.com/amussey/hubot-testing-boilerplate/
[boilerplate]: https://github.com/amussey/hubot-testing-boilerplate/
[ping]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/ping.coffee
[ping-test]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/ping.coffee
[pugme]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/pugme.coffee
[pugme-test]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/pugme.coffee
[secret]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/secret.coffee
[secret-test]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/secret.coffee
[shipit]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/shipit.coffee
[shipit-test]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/shipit.coffee
[remember]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/remember.coffee
[remember-test]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/remember.coffee
[timestamp]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/scripts/timestamp.coffee
[timestamp-test]: https://github.com/amussey/hubot-testing-boilerplate/blob/master/tests/timestamp.coffee
