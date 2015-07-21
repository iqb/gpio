# PHP GPIO abstraction
GPIO abstraction using the linux sysfs interface, mainly for Raspberry PI. 

## Installation

Just put the following in your ```composer.json``` file inside your project root.
No stable version exists so far.

```Json
"require": {
  "iqb/gpio": "*@dev"
}
```

## Features

* ``Pin`` class: abstraction of a single GPIO pin
* ``LCD`` class: abstraction of a HD44780 compatible 16x2 display
* ``PinEmulator`` class: mock replacement for ``Pin`` class
* ``Emulator`` class: uses ``PinEmulator``s to make GPIO using software testable!

### GPIO pin abstraction

The ``Pin`` class provides a simple interface to GPIO pins/ports.
It uses the userspace GPIO interface provided by the linux kernel below ``/sys/class/gpio`` and so will obviously not work on other operating systems.
It is developed on Raspbian Wheezy.

The linux kernel identifies the GPIO pins by numbers.
They can be found using the BCM value of the ``gpio readall`` (you need WiringPi for the command to exist).

```PHP
namespace iqb\gpio;

// Will open the GPIO pin with BCM number 17 = physical pin 11
$pin = new Pin(17);

// A pin must be enabled so it can be used.
// Pin-consumers like the LCD class enable pins themselves when they use them 
$pin->enable();

// Pin is in output mode
$pin->setDirection(Pin::DIRECTION_OUT);

// and the pin is now enabled (sending a 1)
$pin->setValue(true);

// Pin is in input mode now
$pin->setDirection(Pin::DIRECTION_IN);

// Enable edge detection.
// This feature is required to detect changes of the value.
$pin->setEdge(Pin::EDGE_BOTH);

// Read the current value
$value = $pin->getValue();

// We can wait for a change of the pin value using stream_select()
// We need to initialize the file handle sets we want to monitor.
// Changes to GPIO pins are "exceptional" (out of band) events
// so we need the third set of handles.
$read = [];
$write = [];
$except = [$pin->getValueHandle()];
while (true) {
  if (-1 === stream_select($read, $write, $except, 60)) { continue; }
  
  echo "Value changed: " . ($pin->getValue() ? '1' : '0') . "\n";  
}
```

### HD44780 compatible 16x2 display abstraction

This display is connected via 6 GPIO pins.

```PHP
namespace iqb\gpio;

// Create the display, the pins are the actual used pins from my personal project
// You don't need to enable the pins or set the direction, that is done by the LCD class.
$lcd = new LCD(
  new Pin(7),
  new Pin(8),
  new Pin(25),
  new Pin(24),
  new Pin(23),
  new Pin(18)
);

// Enable the pins, set the direction, send some magic commands to the display ...
$lcd->initialize();

// Write something in the first line
$lcd->writeString('abcdefghijklmnop', 1);
// Write something in the second line
$lcd->writeString('0123456789012345', 2);
```
