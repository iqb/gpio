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
