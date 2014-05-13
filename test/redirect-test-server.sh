#!/bin/bash

apache2 -d .. \
    -f test/redirect-test.conf \
    -X
