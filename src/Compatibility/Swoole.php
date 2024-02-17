<?php

if (!\class_exists('\OpenSwoole\Table')) {
    \class_alias('\Swoole\Table', '\OpenSwoole\Table');
}