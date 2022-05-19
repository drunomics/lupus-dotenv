# Per-branch environment variables

Files named the same as the current branch will get sourced, so that 
environment variables may be declared.

Slashes in branch names are replaced by "--", e.g. for "feature/test" a script 
"feature--test.sh" is executed.

Note: The file must be named "${BRANCH}.sh" *and* must be executable.

## Supported variables:

* Custom versions of the ldp-* can be used by exporting the
  variables:

  * LDP_CORE="feature/WV-3185"
  * LDP_ADVERTORIAL="feature/WV-3185"
  * LDP_ISSUU="feature/WV-3185"
  * LDP_SOLR="feature/WV-3185"
  * LDP_OEWA="feature/LDP-932"
  * LDP_CP="feature/LDP-740"
  * LDP_FORM_BUILDER="feature/LDP-867"
  * LDP_SEO_PLUS="feature/LDP-1178"
  * LDP_PROJECT_KICKSTART="feature/WV-3185"
  * LDP_CORE_RECOMMENDED="feature/LDP-1127"
  * LDP_CORE_DEV_RECOMMENDED="feature/LDP-1127"
  
* Custom versions of the lupus-nuxt-kickstart can be used by exporting the variable  
  
  * LUPUS_NUXT_KICKSTART="feature/LDP-526"
  
* PRE_BUILD_COMMANDS may be set and are executed before the project
  is built.
