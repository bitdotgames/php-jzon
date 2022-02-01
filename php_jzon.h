#ifndef PHP_JZON_H
#define PHP_JZON_H

#define JZON_EXT_VERSION "0.0.3"

extern zend_module_entry jzon_module_entry;
#define phpext_jzon_ptr &jzon_module_entry

#ifdef PHP_WIN32
#   define PHP_JZON_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#   define PHP_JZON_API __attribute__ ((visibility("default")))
#else
#   define PHP_JZON_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

#ifdef ZTS
#define JZON_G(v) TSRMG(jzon_globals_id, zend_jzon_globals *, v)
#else
#define JZON_G(v) (jzon_globals.v)
#endif

#endif  /* PHP_JZON_H */
