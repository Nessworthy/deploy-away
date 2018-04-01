# Deploy Away (WIP)

A project involving DeployHQ, a Raspberry Pi, and a big red button.

The project aims to make manual deployments a little more special by hooking physical components to virtual actions.

A user uses a key to turn the lock switch, which "arms" the deploy button. While armed, the user can press the button
which triggers a deployment of the latest revision of a configured branch (see config below).

While deploying, 3 LEDs are utilised to show progress in semi real time (by polling).

After the deployment is completed, the button can be re-armed for deployment again. 

## Components

![Circuit Diagram](diagram.png) 

(TODO: Loop / state diagram).

### Physical

* Raspberry Pi (ye olde 26 pin GPIO) with a WiFi dongle.
* 3 RGB LEDs.
* 9x 100 ohm resistors.
* A big red button (with internal LED).
* A key switch.

### Software

* Raspbian OS
* PHP 7 (with Composer)

### Main PHP Packages

* Auryn - Next-level dependency injector.
    * Saved a ton of time while swapping out components during testing.
    * It's just so cool.
* PHPi - Non-blocking pi GPIO pin control library.
    * Uses React for that sweet sweet asynchronicity.
    * Abstractions made stubbing super easy when testing without the Pi.
* Amp - Non-blocking concurrency framework for PHP.
    * Turns callback hell into generator heaven.
    * `yield` All the things!
* Artax - HTTP client component for Amp.
    * Enables non blocking HTTP requests to third party services.
    * Who needs SDKs anyway?
* ReactAdapter - Amp's answer to working with React's `LoopInterface`.

### External Services

* DeployHQ - Service which enables deploying code to servers.

## Setup

### Config

Hook up the components like the diagram above.

The following should be set up as environment variables, or present in a `.env` file in the top directory:

* `DEPLOY_ACCOUNT` - DeployHQ account.
* `DEPLOY_USER` - DeployHQ user.
* `DEPLOY_KEY` - DeployHQ API key.
* `DEPLOY_SERVER_UUID` - DeployHQ target deployment server UUID.
* `DEPLOY_BRANCH` - DeployHQ repository branch to deploy from.
* `RGB_LED_PIN_1_RED` - The GPIO pin ID for the first LED controlling red.
* `RGB_LED_PIN_1_GREEN` - The GPIO pin ID for the first LED controlling green.
* `RGB_LED_PIN_1_BLUE` - The GPIO pin ID for the first LED controlling blue.
* `RGB_LED_PIN_2_RED` - The GPIO pin ID for the second LED controlling red.
* `RGB_LED_PIN_2_GREEN` - The GPIO pin ID for the second LED controlling green.
* `RGB_LED_PIN_2_BLUE` - The GPIO pin ID for the second LED controlling red.
* `RGB_LED_PIN_3_RED` - The GPIO pin ID for the third LED controlling red.
* `RGB_LED_PIN_3_GREEN` - The GPIO pin ID for the third LED controlling green.
* `RGB_LED_PIN_3_BLUE` - The GPIO pin ID for the third LED controlling blue.
* `BUTTON_OUTPUT_PIN` - The GPIO pin ID for the big-ass button output.
* `BUTTON_LED_PIN` - The GPIO pin ID for the big-ass button LED output.

## Run

Execute `bin/run.php` in your pi. The user must have access to the GPIO directory, which means it should be in the `gpio`
group or have sudo privileges (not recommended).