<form action="{§.action}" method="{§.method}">@§.fieldsets@
  <fieldset%@§.fieldsets:class%
    class="@§.fieldsets:class@"%/@§.fieldsets:class%>
    <legend>{i18n.§.fieldset_@§.fieldsets:name@}</legend>
    <p>@§.fieldsets.@§.fieldsets:n@.fields@
%@§.fieldsets.@§.fieldsets:n@.fields:hidden%
      <input type="hidden" name="@§.fieldsets.@§.fieldsets:n@.fields:name@"
        value="@§.fieldsets.@§.fieldsets:n@.fields:value@" />
%/@§.fieldsets.@§.fieldsets:n@.fields:hidden%%@§.fieldsets.@§.fieldsets:n@.fields:submit%
      <span class="submit">
        <input type="submit" value="{i18n.§.field_@§.fieldsets.@§.fieldsets:n@.fields:name@}" />
      </span>
%/@§.fieldsets.@§.fieldsets:n@.fields:submit%%@§.fieldsets.@§.fieldsets:n@.fields:!hidden%%@§.fieldsets.@§.fieldsets:n@.fields:!submit%
      <span class="fieldrow%@§.fieldsets.@§.fieldsets:n@.fields:class% @§.fieldsets.@§.fieldsets:n@.fields:class@%/@§.fieldsets.@§.fieldsets:n@.fields:class%">
        <label for="@§.fieldsets:name@@§.fieldsets.@§.fieldsets:n@.fields:name@">
          {i18n.§.field_@§.fieldsets.@§.fieldsets:n@.fields:name@}{i18n.typo_colmark_sp}:
        </label>%i18n.§.field_@§.fieldsets.@§.fieldsets:n@.fields:name@_desc%
        <span class="help">{i18n.§.field_@fields.@§.fieldsets:n@:name@_desc}
        </span>%/i18n.§.field_@§.fieldsets.@§.fieldsets:n@.fields:name@_desc%%i18n.§.field_@§.fieldsets.@§.fieldsets:n@.fields:name@_fmt%
        <span class="fmt">{i18n.§.field_@fields.@§.fieldsets:n@:name@_fmt}
        </span>%/i18n.§.field_@§.fieldsets.@§.fieldsets:n@.fields:name@_fmt%

%@§.fieldsets.@§.fieldsets:n@.fields:input%
      <input type="@§.fieldsets.@§.fieldsets:n@.fields:type@"
        id="@§.fieldsets:name@@§.fieldsets.@§.fieldsets:n@.fields:name@"
        name="@§.fieldsets.@§.fieldsets:n@.fields:name@"
        value="@§.fieldsets.@§.fieldsets:n@.fields:value@" />
%/@§.fieldsets.@§.fieldsets:n@.fields:input%

%@§.fieldsets.@§.fieldsets:n@.fields:checkbox%
      <input type="checkbox"
        id="@§.fieldsets:name@@§.fieldsets.@§.fieldsets:n@.fields:name@"
        name="@§.fieldsets.@§.fieldsets:n@.fields:name@"
        value="1" />
%/@§.fieldsets.@§.fieldsets:n@.fields:checkbox%

%@§.fieldsets.@§.fieldsets:n@.fields:textarea%
      <textarea
        id="@§.fieldsets:name@@§.fieldsets.@§.fieldsets:n@.fields:name@"
        name="@§.fieldsets.@§.fieldsets:n@.fields:name@">@§.fieldsets.@§.fieldsets:n@.fields:value@</textarea>
%/@§.fieldsets.@§.fieldsets:n@.fields:textarea%

      </span>
%/@§.fieldsets.@§.fieldsets:n@.fields:!submit%%/@§.fieldsets.@§.fieldsets:n@.fields:!hidden%

    @/§.fieldsets.@§.fieldsets:n@.fields@
    </p>
  </fieldset>@/§.fieldsets@
</form>
