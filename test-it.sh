#!/bin/bash

if [ -z ${TEST_APACHE_HTTP_PORT+x} ]; then
    echo TEST_APACHE_HTTP_PORT is not set.
    exit 1
fi

if [ -z ${TEST_APACHE_HTTPS_PORT+x} ]; then
    echo TEST_APACHE_HTTPS_PORT is not set.
    exit 1
fi

# Create temporary directory.
TMP_APACHE_DIR="$(mktemp -d)"
# trap "rm -Rf $TMP_APACHE_DIR" EXIT

# Transform configuration files.
for CONF in *.conf; do
    if [ "$CONF" != all.conf ]; then
        /bin/sed \
            -e "s%/usr/local/apache/common/%$TMP_APACHE_DIR/mediawiki-config/%" \
            < "$CONF" \
            > "$TMP_APACHE_DIR/$CONF"
    else
        {
            echo "Listen 127.0.0.1:$TEST_APACHE_HTTP_PORT";
            echo 'Include /etc/apache2/mods-enabled/*.load';
            echo 'Include /etc/apache2/mods-enabled/*.conf';
            echo 'Include /etc/apache2/mods-available/expires.load';
            /bin/sed -e s%/etc/apache2/wmf/%% \
            < "$CONF"; \
        } \
            > "$TMP_APACHE_DIR/$CONF"
    fi
done

# Set up empty document roots.
for DOCROOT in advisory auditcom bits board boardgovcom chair chapcom \
checkuser collab commons default donate exec fdc foundation grants \
iegcom incubator internal login m.wikipedia.org mediawiki meta \
movementroles office ombudsmen otrs-wiki outreach quality \
search.wikimedia.org searchcom secure sources spcom species steward \
strategy testwikidata transitionteam usability vote wikibooks.org \
wikidata wikimedia.org wikinews.org wikipedia.org wikiquote.org \
wikisource.org wikiversity.org wikivoyage.org wiktionary.org \
wwwportal; do
    mkdir -p "$TMP_APACHE_DIR"/mediawiki-config/docroot/"$DOCROOT"
done

# Set up logs directory.
mkdir -p "$TMP_APACHE_DIR"/logs

# Start Apache in the background.
# APACHE_PID_FILE="$TMP_APACHE_DIR/apache.pid" \
APACHE_CONFDIR="$TMP_APACHE_DIR" \
APACHE_ULIMIT_MAX_FILES=true \
APACHE_RUN_USER="$LOGNAME" \
APACHE_LOCK_DIR="$TMP_APACHE_DIR" \
APACHE_RUN_DIR="$TMP_APACHE_DIR" \
    /usr/sbin/apachectl \
    -f all.conf

exit 0
