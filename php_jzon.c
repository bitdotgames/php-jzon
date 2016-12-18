#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "zend_exceptions.h"
#include "zend_smart_str.h"
#include "ext/standard/info.h"
#include "php_verdep.h"
#include "php_jzon.h"

/* jzon */
#include "jzon.h"

static ZEND_FUNCTION(jzon_parse_c);

ZEND_BEGIN_ARG_INFO_EX(arginfo_jzon_parce_c, 0, 0, 1)
    ZEND_ARG_INFO(0, data)
ZEND_END_ARG_INFO()

static zend_function_entry jzon_functions[] = {
    ZEND_FE(jzon_parse_c, arginfo_jzon_parce_c)
    ZEND_FE_END
};

ZEND_MINFO_FUNCTION(jzon)
{
    char buffer[128];
    php_info_print_table_start();
    php_info_print_table_row(2, "JZON support", "enabled");
    php_info_print_table_row(2, "Extension Version", JZON_EXT_VERSION);
    php_info_print_table_end();
}

zend_module_entry jzon_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
    STANDARD_MODULE_HEADER,
#endif
    "jzon",
    jzon_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    ZEND_MINFO(jzon),
#if ZEND_MODULE_API_NO >= 20010901
    JZON_EXT_VERSION,
#endif
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_JZON
ZEND_GET_MODULE(jzon)
#endif

static void conv_value(const JzonValue* jv, zval* zv)
{
  if(jv->is_array)
  {
    array_init(zv);
    for(int i=0;i<jv->size;++i)
    {
      zval arr_zv;
      JzonValue* arr_jv = jv->array_values[i];
      conv_value(arr_jv, &arr_zv);
      add_index_zval(zv, i, &arr_zv);
    }
  }
  else if(jv->is_object)
  {
    array_init(zv);
    for(int i=0;i<jv->size;++i)
    {
      zval arr_zv;
      JzonKeyValuePair* obj_jv = jv->object_values[i];
      conv_value(obj_jv->value, &arr_zv);
      add_assoc_zval(zv, obj_jv->key, &arr_zv);
    }
  }
  else if(jv->is_int)
  {
    ZVAL_LONG(zv, jv->int_value);
  }
  else if(jv->is_float)
  {
    ZVAL_DOUBLE(zv, jv->float_value);
  }
  else if(jv->is_string)
  {
    ZVAL_STRING(zv, jv->string_value);
  }
  else if(jv->is_bool)
  {
    ZVAL_BOOL(zv, jv->bool_value);
  }
  else if(jv->is_null)
  {
    ZVAL_NULL(zv);
  }
}

static ZEND_FUNCTION(jzon_parse_c)
{
  zval* data;
  if(zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                           "z", &data) == FAILURE) 
  {
    RETURN_FALSE;
  }

  if(Z_TYPE_P(data) != IS_STRING) 
  {
    zend_error(E_WARNING,
                "jzon_parse_c : expects parameter to be string.");
    RETURN_FALSE;
  }

  array_init(return_value);

  JzonParseResult res = jzon_parse(Z_STRVAL_P(data));

  zval zv;
  ZVAL_NULL(&zv);

  if(res.success)
  {
    JzonValue* jv = res.output;
    conv_value(jv, &zv);

    if(jv != NULL)
      jzon_free(jv);

    add_index_bool(return_value, 0, 1);
    add_index_string(return_value, 1, "");
    add_index_long(return_value, 2, 0);
    add_index_zval(return_value, 3, &zv);
  }
  else
  {
    add_index_bool(return_value, 0, 0);
    add_index_string(return_value, 1, res.error);
    add_index_long(return_value, 2, res.error_pos);
    add_index_zval(return_value, 3, &zv);
  }
}
