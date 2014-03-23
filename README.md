# Sleepy, the lightweight web application framework

## Introduction

Sleepy is a web application framework with a client - server architecture and a
lightweight approach. It attempts to provide the bare essentials for building a
web application, is opinionated but tries to keep its conventions at a minimum.

This repository contains the client part of Sleepy, written in PHP. It consists
of a thin wrapper around the server part, exposing functionality in an idiomatic
way, as well as parts that only make sense as part of the client (routing, MVC,
inflection, etc.). Its conventions and API are designed to be directly used by
web applications built on Sleepy.

The client design is largely based on conventions used by the CakePHP and
CodeIgniter frameworks, especially the router, dispatcher and database module
parts.

Be aware that this is ALPHA SOFTWARE, and as such is functionally incomplete and
quite possibly contains bugs. Don't say I didn't warn you.

## Installation/Configuration

Simply place the contents of the repository in a directory that is readable by
applications built on Sleepy. The default location is in "/srv/sleepy", but any
directory will do. Write permissions are not required.

Configuration defaults will suffice, unless defaults were also changed in the
Sleepy server.

## More documentation

The client contains documentation designed to be generated by tools like Doxygen
or PHPdocumentor. More in-depth documentation, as well as a tutorial on making
a simple Sleepy application will follow shortly.

## License

The Sleepy client is licensed under the terms of AGPL, version 3.