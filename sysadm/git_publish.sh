#!/bin/bash
HOMEDIR=/home/ec2-user
GITDIR=$HOMEDIR/git
WWWDIR=$HOMEDIR/htdoc

cd $GITDIR;
git pull;
rsync $GITDIR/lego/htdoc/* $WWWDIR;
