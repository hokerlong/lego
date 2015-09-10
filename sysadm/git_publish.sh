#!/bin/bash
HOMEDIR=/home/ec2-user
GITDIR=$HOMEDIR/git/lego
WWWDIR=$HOMEDIR/htdoc

cd $GITDIR;
git pull;

rsync -r --delete --exclude="conn.php" $GITDIR/htdoc/ $WWWDIR;
