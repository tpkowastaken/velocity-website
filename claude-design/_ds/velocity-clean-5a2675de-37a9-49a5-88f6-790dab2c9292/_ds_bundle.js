/* @ds-bundle: {"format":4,"namespace":"VelocityClean_5a2675","components":[{"name":"Banner","sourcePath":"components/brand/Banner.jsx"},{"name":"Logo","sourcePath":"components/brand/Logo.jsx"},{"name":"Badge","sourcePath":"components/display/Badge.jsx"},{"name":"Card","sourcePath":"components/display/Card.jsx"},{"name":"Tag","sourcePath":"components/display/Tag.jsx"},{"name":"Alert","sourcePath":"components/feedback/Alert.jsx"},{"name":"Tooltip","sourcePath":"components/feedback/Tooltip.jsx"},{"name":"Button","sourcePath":"components/forms/Button.jsx"},{"name":"Checkbox","sourcePath":"components/forms/Checkbox.jsx"},{"name":"IconButton","sourcePath":"components/forms/IconButton.jsx"},{"name":"Input","sourcePath":"components/forms/Input.jsx"},{"name":"Radio","sourcePath":"components/forms/Radio.jsx"},{"name":"Select","sourcePath":"components/forms/Select.jsx"},{"name":"Switch","sourcePath":"components/forms/Switch.jsx"},{"name":"Tabs","sourcePath":"components/navigation/Tabs.jsx"}],"sourceHashes":{"components/brand/Banner.jsx":"b95f43ba2d15","components/brand/Logo.jsx":"74aedc807d1a","components/display/Badge.jsx":"3a4aeff38a5c","components/display/Card.jsx":"134aa1068009","components/display/Tag.jsx":"aaf7cec2c5ec","components/feedback/Alert.jsx":"abee1c61942f","components/feedback/Tooltip.jsx":"dc79888f1393","components/forms/Button.jsx":"8c62cfce111f","components/forms/Checkbox.jsx":"09aa59790c5d","components/forms/IconButton.jsx":"f66982613850","components/forms/Input.jsx":"d7f8308c1f18","components/forms/Radio.jsx":"ea403d16bfaf","components/forms/Select.jsx":"d151ba384826","components/forms/Switch.jsx":"36240db5bd78","components/navigation/Tabs.jsx":"0078f5965e60","ui_kits/website/Site.jsx":"c691e9a0d4e0"},"inlinedExternals":[],"unexposedExports":[]} */

(() => {

const __ds_ns = (window.VelocityClean_5a2675 = window.VelocityClean_5a2675 || {});

const __ds_scope = {};

(__ds_ns.__errors = __ds_ns.__errors || []);

// components/brand/Banner.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * Diagonal-split CTA banner. Bedrock field with a clipped voltage wedge.
 */
function Banner({
  title,
  subtitle,
  action,
  style = {},
  ...rest
}) {
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      position: 'relative',
      overflow: 'hidden',
      borderRadius: 'var(--radius-md)',
      background: 'linear-gradient(135deg, var(--bedrock) 0%, #050505 100%)',
      padding: 'var(--space-7)',
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'absolute',
      top: 0,
      right: 0,
      width: '34%',
      height: '100%',
      background: 'linear-gradient(135deg, var(--voltage) 0%, var(--voltage-deep) 100%)',
      clipPath: 'polygon(45% 0, 100% 0, 100% 100%, 10% 100%)'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative',
      maxWidth: 460
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 28,
      color: 'var(--signal)',
      lineHeight: 0.95,
      letterSpacing: '0.02em',
      textTransform: 'uppercase'
    }
  }, title), subtitle && /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 14,
      color: 'var(--text-muted-inverse)',
      marginTop: 'var(--space-3)'
    }
  }, subtitle), action && /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 'var(--space-5)'
    }
  }, action)));
}
Object.assign(__ds_scope, { Banner });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/brand/Banner.jsx", error: String((e && e.message) || e) }); }

// components/brand/Logo.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const PATH = 'M318.629,122.715S340.436,262.284,585.742,264.5c250.867,2.27,274.886-143.218,274.886-143.218l181-.143L971.743,266.2s-170.807-16.682-220.928,90.175c64.477-.518,177-0.14,177-0.14L590.371,1059.5,251.815,356.768l176-.139L590.086,698.5,750.815,356.373l-323,.256S386.66,238.424,207.743,265.8C151.358,148.717,138.63,122.858,138.63,122.858Z';
const FILLS = {
  voltage: 'var(--voltage)',
  white: 'var(--signal)',
  black: 'var(--bedrock)'
};

/**
 * Velocity "V" mark. Inline SVG — colorable via `variant`, or set `fill`
 * directly. Never stretch or rotate; keep ½-height clearspace.
 */
function Logo({
  variant = 'voltage',
  fill,
  size = 48,
  title = 'Velocity',
  style = {},
  ...rest
}) {
  const color = fill || FILLS[variant] || FILLS.voltage;
  return /*#__PURE__*/React.createElement("svg", _extends({
    viewBox: "0 0 1181 1181",
    width: size,
    height: size,
    role: "img",
    "aria-label": title,
    style: {
      display: 'block',
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("title", null, title), /*#__PURE__*/React.createElement("path", {
    d: PATH,
    fill: color
  }));
}
Object.assign(__ds_scope, { Logo });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/brand/Logo.jsx", error: String((e && e.message) || e) }); }

// components/display/Badge.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Small status/step marker. Square edges, tiny radius. */
function Badge({
  children,
  variant = 'dark',
  style = {},
  ...rest
}) {
  const palettes = {
    dark: {
      background: 'var(--bedrock)',
      color: 'var(--signal)',
      border: 'none'
    },
    voltage: {
      background: 'var(--voltage)',
      color: 'var(--bedrock)',
      border: 'none'
    },
    outline: {
      background: 'transparent',
      color: 'var(--bedrock)',
      border: '1.5px solid var(--bedrock)'
    }
  };
  const pal = palettes[variant] || palettes.dark;
  return /*#__PURE__*/React.createElement("span", _extends({
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      fontFamily: 'var(--font-body)',
      fontSize: 12,
      fontWeight: 600,
      padding: '4px 10px',
      borderRadius: 'var(--radius-sm)',
      lineHeight: 1.2,
      ...pal,
      ...style
    }
  }, rest), children);
}
Object.assign(__ds_scope, { Badge });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/display/Badge.jsx", error: String((e && e.message) || e) }); }

// components/display/Card.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Content card. Light Signal surface by default; `dark` for bedrock. */
function Card({
  title,
  children,
  dark = false,
  style = {},
  bodyStyle = {},
  ...rest
}) {
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      border: dark ? '1px solid var(--border-inverse)' : '1px solid var(--border-subtle)',
      borderRadius: 'var(--radius-md)',
      background: dark ? 'linear-gradient(135deg, var(--bedrock) 0%, #050505 100%)' : 'linear-gradient(135deg, var(--surface-card) 0%, #e9e9e7 100%)',
      boxShadow: 'var(--shadow-card)',
      padding: 'var(--space-5)',
      color: dark ? 'var(--signal)' : 'var(--text-body)',
      ...style
    }
  }, rest), title && /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 22,
      letterSpacing: '0.02em',
      marginBottom: 'var(--space-2)',
      color: dark ? 'var(--signal)' : 'var(--text-strong)'
    }
  }, title), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 13,
      lineHeight: 1.5,
      color: dark ? 'var(--text-muted-inverse)' : 'var(--text-muted)',
      ...bodyStyle
    }
  }, children));
}
Object.assign(__ds_scope, { Card });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/display/Card.jsx", error: String((e && e.message) || e) }); }

// components/display/Tag.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Pill-shaped filter/category tag. */
function Tag({
  children,
  active = false,
  style = {},
  ...rest
}) {
  return /*#__PURE__*/React.createElement("span", _extends({
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      fontFamily: 'var(--font-body)',
      fontSize: 13,
      padding: '6px 14px',
      borderRadius: 'var(--radius-pill)',
      cursor: rest.onClick ? 'pointer' : 'default',
      background: active ? 'var(--bedrock)' : 'var(--signal)',
      color: active ? 'var(--signal)' : 'var(--text-body)',
      border: `1px solid ${active ? 'var(--bedrock)' : 'var(--border-subtle)'}`,
      transition: 'background var(--dur-base), color var(--dur-base)',
      ...style
    }
  }, rest), children);
}
Object.assign(__ds_scope, { Tag });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/display/Tag.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Alert.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const TONES = {
  success: {
    bg: 'var(--success-bg)',
    fg: 'var(--success-fg)',
    bd: 'var(--success-border)'
  },
  error: {
    bg: 'var(--error-bg)',
    fg: 'var(--error-fg)',
    bd: 'var(--error-border)'
  },
  warning: {
    bg: 'var(--warn-bg)',
    fg: 'var(--warn-fg)',
    bd: 'var(--warn-border)'
  }
};

/** Inline notification banner. */
function Alert({
  tone = 'success',
  title,
  children,
  style = {},
  ...rest
}) {
  const t = TONES[tone] || TONES.success;
  return /*#__PURE__*/React.createElement("div", _extends({
    role: "status",
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 2,
      fontFamily: 'var(--font-body)',
      fontSize: 14,
      padding: title ? 'var(--space-4)' : 'var(--space-3) var(--space-4)',
      borderRadius: 'var(--radius-sm)',
      background: t.bg,
      color: t.fg,
      border: `1px solid ${t.bd}`,
      ...style
    }
  }, rest), title && /*#__PURE__*/React.createElement("div", {
    style: {
      fontWeight: 600
    }
  }, title), children && /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: title ? 13 : 14
    }
  }, children));
}
Object.assign(__ds_scope, { Alert });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Alert.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Tooltip.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Hover tooltip. Wrap a trigger; `label` is the bubble text. */
function Tooltip({
  label,
  children,
  style = {},
  ...rest
}) {
  const [open, setOpen] = React.useState(false);
  return /*#__PURE__*/React.createElement("span", _extends({
    style: {
      position: 'relative',
      display: 'inline-block',
      ...style
    },
    onMouseEnter: () => setOpen(true),
    onMouseLeave: () => setOpen(false)
  }, rest), children, /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      bottom: 'calc(100% + 10px)',
      left: '50%',
      transform: 'translateX(-50%)',
      background: 'var(--bedrock)',
      color: 'var(--signal)',
      fontFamily: 'var(--font-body)',
      fontSize: 12,
      padding: '6px 10px',
      borderRadius: 'var(--radius-sm)',
      whiteSpace: 'nowrap',
      pointerEvents: 'none',
      opacity: open ? 1 : 0,
      transition: 'opacity var(--dur-base) var(--ease-out)',
      zIndex: 20
    }
  }, label, /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      top: '100%',
      left: '50%',
      transform: 'translateX(-50%)',
      border: '5px solid transparent',
      borderTopColor: 'var(--bedrock)'
    }
  })));
}
Object.assign(__ds_scope, { Tooltip });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Tooltip.jsx", error: String((e && e.message) || e) }); }

// components/forms/Button.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * Velocity Button. Sharp-edged by default. Two type modes:
 * body (Poppins semibold) and display (Bebas Neue, uppercase, tracked).
 */
function Button({
  children,
  variant = 'primary',
  // 'primary' | 'dark' | 'outline'
  display = false,
  onDark = false,
  size = 'md',
  // 'sm' | 'md' | 'lg'
  fullWidth = false,
  disabled = false,
  style = {},
  ...rest
}) {
  const pad = {
    sm: '10px 20px',
    md: '14px 28px',
    lg: '18px 36px'
  }[size];
  const palettes = {
    primary: {
      background: 'var(--voltage)',
      color: 'var(--bedrock)',
      border: '1.5px solid var(--voltage)'
    },
    dark: {
      background: 'var(--bedrock)',
      color: 'var(--signal)',
      border: '1.5px solid var(--bedrock)'
    },
    outline: {
      background: 'transparent',
      color: onDark ? 'var(--voltage)' : 'var(--bedrock)',
      border: `1.5px solid ${onDark ? 'var(--voltage)' : 'var(--bedrock)'}`
    }
  };
  // On a voltage/dark surface, "dark" solid flips to voltage-on-bedrock stays; primary on dark uses voltage.
  const pal = palettes[variant] || palettes.primary;
  const typeStyle = display ? {
    fontFamily: 'var(--font-display)',
    letterSpacing: 'var(--ls-caps)',
    fontSize: size === 'lg' ? '23px' : '19px',
    textTransform: 'uppercase'
  } : {
    fontFamily: 'var(--font-body)',
    fontWeight: 600,
    fontSize: size === 'lg' ? '15px' : '14px'
  };
  return /*#__PURE__*/React.createElement("button", _extends({
    disabled: disabled,
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      gap: 'var(--space-2)',
      padding: pad,
      minWidth: 140,
      borderRadius: 'var(--radius-none)',
      cursor: disabled ? 'not-allowed' : 'pointer',
      width: fullWidth ? '100%' : undefined,
      opacity: disabled ? 0.4 : 1,
      transition: 'transform var(--dur-fast) var(--ease-out), opacity var(--dur-base)',
      ...pal,
      ...typeStyle,
      ...style
    },
    onMouseDown: e => {
      if (!disabled) e.currentTarget.style.transform = 'scale(0.96)';
    },
    onMouseUp: e => {
      e.currentTarget.style.transform = 'scale(1)';
    },
    onMouseLeave: e => {
      e.currentTarget.style.transform = 'scale(1)';
    }
  }, rest), children);
}
Object.assign(__ds_scope, { Button });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Button.jsx", error: String((e && e.message) || e) }); }

// components/forms/Checkbox.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Square checkbox with voltage fill + bedrock check when selected. */
function Checkbox({
  label,
  checked,
  defaultChecked,
  onChange,
  disabled = false,
  style = {},
  ...rest
}) {
  return /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: 'var(--space-3)',
      fontSize: 14,
      fontFamily: 'var(--font-body)',
      color: 'var(--text-body)',
      cursor: disabled ? 'not-allowed' : 'pointer',
      opacity: disabled ? 0.5 : 1,
      ...style
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'relative',
      width: 18,
      height: 18,
      flex: 'none'
    }
  }, /*#__PURE__*/React.createElement("input", _extends({
    type: "checkbox",
    checked: checked,
    defaultChecked: defaultChecked,
    onChange: onChange,
    disabled: disabled,
    style: {
      appearance: 'none',
      WebkitAppearance: 'none',
      width: 18,
      height: 18,
      margin: 0,
      border: '1.5px solid var(--bedrock)',
      background: 'var(--white)',
      cursor: 'inherit'
    }
  }, rest)), /*#__PURE__*/React.createElement("span", {
    "aria-hidden": true,
    style: {
      position: 'absolute',
      inset: 0,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontSize: 12,
      color: 'var(--bedrock)',
      pointerEvents: 'none'
    },
    className: "vx-check"
  }, "\u2713"), /*#__PURE__*/React.createElement("style", null, `.vx-check{opacity:0}input:checked + .vx-check{opacity:1}input:checked{background:var(--voltage)!important}`)), label);
}
Object.assign(__ds_scope, { Checkbox });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Checkbox.jsx", error: String((e && e.message) || e) }); }

// components/forms/IconButton.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Square 48×48 icon button. Pass a glyph/element as children. */
function IconButton({
  children,
  variant = 'primary',
  // 'primary' | 'dark' | 'outline' | 'ghost'
  size = 44,
  onDark = false,
  disabled = false,
  style = {},
  ...rest
}) {
  const palettes = {
    primary: {
      background: 'var(--voltage)',
      color: 'var(--bedrock)',
      border: 'none'
    },
    dark: {
      background: 'var(--bedrock)',
      color: 'var(--signal)',
      border: 'none'
    },
    outline: {
      background: 'transparent',
      color: onDark ? 'var(--signal)' : 'var(--bedrock)',
      border: `1.5px solid ${onDark ? 'var(--border-inverse)' : 'var(--bedrock)'}`
    },
    ghost: {
      background: 'transparent',
      color: onDark ? 'var(--signal)' : 'var(--bedrock)',
      border: `1.5px solid ${onDark ? 'var(--border-inverse)' : 'var(--border-subtle)'}`
    }
  };
  const pal = palettes[variant] || palettes.primary;
  return /*#__PURE__*/React.createElement("button", _extends({
    disabled: disabled,
    style: {
      width: size,
      height: size,
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      borderRadius: 'var(--radius-none)',
      cursor: disabled ? 'not-allowed' : 'pointer',
      fontSize: 22,
      fontWeight: 800,
      opacity: disabled ? 0.4 : 1,
      transition: 'transform var(--dur-fast) var(--ease-out)',
      ...pal,
      ...style
    },
    onMouseDown: e => {
      if (!disabled) e.currentTarget.style.transform = 'scale(0.92)';
    },
    onMouseUp: e => {
      e.currentTarget.style.transform = 'scale(1)';
    },
    onMouseLeave: e => {
      e.currentTarget.style.transform = 'scale(1)';
    }
  }, rest), children);
}
Object.assign(__ds_scope, { IconButton });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/IconButton.jsx", error: String((e && e.message) || e) }); }

// components/forms/Input.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Text input with optional label. Uses the Velocity field styling. */
function Input({
  label,
  id,
  required = false,
  style = {},
  wrapStyle = {},
  ...rest
}) {
  const inputId = id || (label ? `vx-${label.replace(/\s+/g, '-').toLowerCase()}` : undefined);
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      ...wrapStyle
    }
  }, label && /*#__PURE__*/React.createElement("label", {
    htmlFor: inputId,
    style: {
      display: 'block',
      fontSize: 12,
      fontWeight: 600,
      color: 'var(--text-muted)',
      marginBottom: 'var(--space-2)',
      letterSpacing: '0.02em'
    }
  }, label, required ? ' *' : ''), /*#__PURE__*/React.createElement("input", _extends({
    id: inputId,
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 15,
      color: 'var(--text-strong)',
      border: '1.5px solid var(--border-subtle)',
      borderRadius: 'var(--radius-sm)',
      padding: '12px 14px',
      width: '100%',
      boxSizing: 'border-box',
      background: 'var(--white)',
      outline: 'none',
      transition: 'border-color var(--dur-base)',
      ...style
    },
    onFocus: e => {
      e.currentTarget.style.borderColor = 'var(--bedrock)';
    },
    onBlur: e => {
      e.currentTarget.style.borderColor = 'var(--border-subtle)';
    }
  }, rest)));
}
Object.assign(__ds_scope, { Input });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Input.jsx", error: String((e && e.message) || e) }); }

// components/forms/Radio.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Radio button — bedrock dot when selected. Group with a shared `name`. */
function Radio({
  label,
  name,
  value,
  checked,
  defaultChecked,
  onChange,
  disabled = false,
  style = {},
  ...rest
}) {
  return /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: 'var(--space-3)',
      fontSize: 14,
      fontFamily: 'var(--font-body)',
      color: 'var(--text-body)',
      cursor: disabled ? 'not-allowed' : 'pointer',
      opacity: disabled ? 0.5 : 1,
      ...style
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'relative',
      width: 18,
      height: 18,
      flex: 'none'
    }
  }, /*#__PURE__*/React.createElement("input", _extends({
    type: "radio",
    name: name,
    value: value,
    checked: checked,
    defaultChecked: defaultChecked,
    onChange: onChange,
    disabled: disabled,
    style: {
      appearance: 'none',
      WebkitAppearance: 'none',
      width: 18,
      height: 18,
      margin: 0,
      borderRadius: '50%',
      border: '1.5px solid var(--bedrock)',
      background: 'var(--white)',
      cursor: 'inherit'
    }
  }, rest)), /*#__PURE__*/React.createElement("span", {
    "aria-hidden": true,
    className: "vx-dot",
    style: {
      position: 'absolute',
      inset: 3,
      borderRadius: '50%',
      background: 'var(--bedrock)',
      pointerEvents: 'none'
    }
  }), /*#__PURE__*/React.createElement("style", null, `.vx-dot{opacity:0}input:checked + .vx-dot{opacity:1}`)), label);
}
Object.assign(__ds_scope, { Radio });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Radio.jsx", error: String((e && e.message) || e) }); }

// components/forms/Select.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Native select styled to match Velocity fields, with a caret. */
function Select({
  label,
  id,
  required = false,
  children,
  style = {},
  wrapStyle = {},
  ...rest
}) {
  const selId = id || (label ? `vx-${label.replace(/\s+/g, '-').toLowerCase()}` : undefined);
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      ...wrapStyle
    }
  }, label && /*#__PURE__*/React.createElement("label", {
    htmlFor: selId,
    style: {
      display: 'block',
      fontSize: 12,
      fontWeight: 600,
      color: 'var(--text-muted)',
      marginBottom: 'var(--space-2)',
      letterSpacing: '0.02em'
    }
  }, label, required ? ' *' : ''), /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative'
    }
  }, /*#__PURE__*/React.createElement("select", _extends({
    id: selId,
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 15,
      color: 'var(--text-strong)',
      border: '1.5px solid var(--border-subtle)',
      borderRadius: 'var(--radius-sm)',
      padding: '12px 40px 12px 14px',
      width: '100%',
      boxSizing: 'border-box',
      background: 'var(--white)',
      appearance: 'none',
      WebkitAppearance: 'none',
      outline: 'none',
      cursor: 'pointer',
      ...style
    },
    onFocus: e => {
      e.currentTarget.style.borderColor = 'var(--bedrock)';
    },
    onBlur: e => {
      e.currentTarget.style.borderColor = 'var(--border-subtle)';
    }
  }, rest), children), /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      right: 14,
      top: '50%',
      transform: 'translateY(-50%)',
      pointerEvents: 'none',
      fontSize: 12,
      color: 'var(--text-muted)'
    }
  }, "\u25BC")));
}
Object.assign(__ds_scope, { Select });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Select.jsx", error: String((e && e.message) || e) }); }

// components/forms/Switch.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/** Toggle switch — voltage track + bedrock knob when on. */
function Switch({
  label,
  checked,
  defaultChecked,
  onChange,
  disabled = false,
  style = {},
  ...rest
}) {
  return /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: 'var(--space-3)',
      fontSize: 14,
      fontFamily: 'var(--font-body)',
      color: 'var(--text-body)',
      cursor: disabled ? 'not-allowed' : 'pointer',
      opacity: disabled ? 0.5 : 1,
      ...style
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'relative',
      width: 40,
      height: 22,
      flex: 'none'
    }
  }, /*#__PURE__*/React.createElement("input", _extends({
    type: "checkbox",
    checked: checked,
    defaultChecked: defaultChecked,
    onChange: onChange,
    disabled: disabled,
    style: {
      appearance: 'none',
      WebkitAppearance: 'none',
      width: 40,
      height: 22,
      margin: 0,
      borderRadius: 999,
      background: 'var(--border-subtle)',
      cursor: 'inherit',
      transition: 'background var(--dur-base) var(--ease-out)'
    },
    className: "vx-switch-input"
  }, rest)), /*#__PURE__*/React.createElement("span", {
    "aria-hidden": true,
    className: "vx-knob",
    style: {
      position: 'absolute',
      top: 2,
      left: 2,
      width: 18,
      height: 18,
      borderRadius: '50%',
      background: 'var(--bedrock)',
      pointerEvents: 'none',
      transition: 'left var(--dur-base) var(--ease-out)'
    }
  }), /*#__PURE__*/React.createElement("style", null, `.vx-switch-input:checked{background:var(--voltage)!important}.vx-switch-input:checked + .vx-knob{left:20px}`)), label);
}
Object.assign(__ds_scope, { Switch });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Switch.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Tabs.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * Underline tabs. `items`: [{ id, label, content }]. Voltage underline on active.
 */
function Tabs({
  items = [],
  defaultId,
  style = {},
  ...rest
}) {
  const [active, setActive] = React.useState(defaultId || items[0] && items[0].id);
  const current = items.find(i => i.id === active) || items[0];
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      fontFamily: 'var(--font-body)',
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 'var(--space-6)',
      borderBottom: '1.5px solid var(--border-subtle)'
    }
  }, items.map(it => {
    const on = it.id === active;
    return /*#__PURE__*/React.createElement("button", {
      key: it.id,
      onClick: () => setActive(it.id),
      style: {
        fontFamily: 'var(--font-body)',
        fontSize: 14,
        background: 'none',
        border: 'none',
        cursor: 'pointer',
        padding: '0 0 12px',
        marginBottom: -1.5,
        color: on ? 'var(--text-strong)' : 'var(--text-muted)',
        fontWeight: on ? 600 : 400,
        borderBottom: `2px solid ${on ? 'var(--voltage)' : 'transparent'}`,
        transition: 'color var(--dur-base)'
      }
    }, it.label);
  })), current && /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 14,
      color: 'var(--text-muted)',
      paddingTop: 'var(--space-4)',
      lineHeight: 1.5
    }
  }, current.content));
}
Object.assign(__ds_scope, { Tabs });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Tabs.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/Site.jsx
try { (() => {
/* Velocity marketing website — UI kit sections.
   Composes design-system primitives from window.VelocityClean_5a2675.
   Exports section components to window for index.html. */
const VX = window.VelocityClean_5a2675;
const {
  Button,
  IconButton,
  Logo,
  Card,
  Tag,
  Badge,
  Banner,
  Input,
  Select,
  Checkbox,
  Alert,
  Tabs
} = VX;
const ASSETS = '../../assets';
function SiteHeader({
  onCta
}) {
  const links = ['Služby', 'Proces', 'Reference', 'Tým'];
  return /*#__PURE__*/React.createElement("header", {
    style: {
      position: 'sticky',
      top: 0,
      zIndex: 30,
      background: 'rgba(11,11,11,0.86)',
      backdropFilter: 'blur(12px)',
      borderBottom: '1px solid var(--border-inverse)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 1200,
      margin: '0 auto',
      padding: '16px 48px',
      display: 'flex',
      alignItems: 'center',
      gap: 32
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 12
    }
  }, /*#__PURE__*/React.createElement(Logo, {
    variant: "voltage",
    size: 34
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 26,
      letterSpacing: '0.06em',
      color: 'var(--signal)'
    }
  }, "VELOCITY")), /*#__PURE__*/React.createElement("nav", {
    style: {
      display: 'flex',
      gap: 28,
      marginLeft: 'auto'
    }
  }, links.map(l => /*#__PURE__*/React.createElement("a", {
    key: l,
    href: "#",
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 14,
      color: 'var(--text-muted-inverse)',
      textDecoration: 'none'
    },
    onMouseEnter: e => e.currentTarget.style.color = 'var(--voltage)',
    onMouseLeave: e => e.currentTarget.style.color = 'var(--text-muted-inverse)'
  }, l))), /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    size: "sm",
    onClick: onCta
  }, "Objednat audit")));
}
function Hero({
  onCta
}) {
  return /*#__PURE__*/React.createElement("section", {
    style: {
      background: 'var(--bedrock)',
      position: 'relative',
      overflow: 'hidden'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 1200,
      margin: '0 auto',
      padding: '96px 48px',
      display: 'grid',
      gridTemplateColumns: '1.1fr 0.9fr',
      gap: 48,
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'inline-flex',
      gap: 8,
      marginBottom: 24
    }
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: "voltage"
  }, "Gen Z marketing"), /*#__PURE__*/React.createElement(Badge, {
    variant: "outline",
    style: {
      color: 'var(--signal)',
      borderColor: 'var(--border-inverse)'
    }
  }, "Kreativn\xED agentura")), /*#__PURE__*/React.createElement("h1", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 88,
      lineHeight: 0.87,
      letterSpacing: '0.01em',
      color: 'var(--signal)',
      margin: 0
    }
  }, "AUTENTICITU", /*#__PURE__*/React.createElement("br", null), "NEUVID\xCDTE ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--voltage)'
    }
  }, "V TABULCE.")), /*#__PURE__*/React.createElement("p", {
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 17,
      lineHeight: 1.6,
      color: 'var(--text-muted-inverse)',
      maxWidth: 500,
      margin: '24px 0 0'
    }
  }, "Stav\xEDme obsah pro generaci Z, kter\xFD skute\u010Dn\u011B funguje \u2014 a dok\xE1\u017Eeme to \u010D\xEDsly. V\u0161e. M\u011B\u0159iteln\xE9. V \u010Dase."), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 12,
      marginTop: 32
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    onClick: onCta
  }, "Objednat Gen Z Audit"), /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    onDark: true
  }, "Jak pracujeme"))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: `${ASSETS}/brand/chrome-v.png`,
    alt: "Velocity",
    style: {
      width: 380,
      filter: 'drop-shadow(0 24px 60px rgba(0,0,0,0.6))'
    }
  }))));
}
function Services() {
  const services = [{
    t: 'Gen Z Audit',
    d: 'Diagnostika vašeho obsahu. Výstup je Gen Z skóre a jasný akční plán.',
    step: 'Krok 1',
    price: '40 000 Kč'
  }, {
    t: 'Obsahová strategie',
    d: 'Deep research na industry, klienta a konkurenci. Staví na auditu.',
    step: 'Krok 2',
    price: '20 000 Kč'
  }, {
    t: 'Core Offer',
    d: '~20 kusů obsahu měsíčně — videa, statiky, grafiky, distribuce.',
    step: 'Krok 3',
    price: '70 000 Kč/měs'
  }];
  return /*#__PURE__*/React.createElement("section", {
    style: {
      background: 'var(--white)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 1200,
      margin: '0 auto',
      padding: '96px 48px'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-end',
      justifyContent: 'space-between',
      marginBottom: 48
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 12,
      fontWeight: 600,
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      marginBottom: 8
    }
  }, "Co d\u011Bl\xE1me"), /*#__PURE__*/React.createElement("h2", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 52,
      lineHeight: 0.9,
      letterSpacing: '0.02em',
      margin: 0,
      color: 'var(--text-strong)'
    }
  }, "T\u0158I KROKY K OBSAHU,", /*#__PURE__*/React.createElement("br", null), "KTER\xDD PROD\xC1V\xC1")), /*#__PURE__*/React.createElement(Tabs, {
    style: {
      minWidth: 300
    },
    items: [{
      id: 'z',
      label: 'Fashion',
      content: 'Streetwear značky, které chtějí mluvit řečí Gen Z.'
    }, {
      id: 'b',
      label: 'Beauty',
      content: 'Personal care s důrazem na autentické UGC.'
    }, {
      id: 's',
      label: 'Supplements',
      content: 'Sportovní výživa a wellness s měřitelným dosahem.'
    }]
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(3,1fr)',
      gap: 24
    }
  }, services.map((s, i) => /*#__PURE__*/React.createElement(Card, {
    key: s.t,
    dark: i === 2,
    title: s.t,
    style: {
      display: 'flex',
      flexDirection: 'column'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8,
      marginBottom: 12
    }
  }, /*#__PURE__*/React.createElement(Badge, {
    variant: i === 2 ? 'voltage' : 'dark'
  }, s.step)), /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: 20
    }
  }, s.d), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 'auto',
      display: 'flex',
      alignItems: 'baseline',
      justifyContent: 'space-between'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 26,
      letterSpacing: '0.02em',
      color: i === 2 ? 'var(--voltage)' : 'var(--text-strong)'
    }
  }, s.price), /*#__PURE__*/React.createElement(IconButton, {
    variant: i === 2 ? 'primary' : 'dark',
    size: 40,
    "aria-label": "detail"
  }, "\u2192")))))));
}
function TeamStrip() {
  const people = [{
    img: 'portrait-1.jpg',
    n: 'Adam Novák',
    r: 'Creative Director'
  }, {
    img: 'portrait-2.jpg',
    n: 'Tomáš Beneš',
    r: 'Head of Content'
  }, {
    img: 'portrait-4.jpg',
    n: 'Jan Dvořák',
    r: 'Strategist'
  }, {
    img: 'portrait-3.jpg',
    n: 'Petr Kučera',
    r: 'Account Lead'
  }];
  return /*#__PURE__*/React.createElement("section", {
    style: {
      background: 'var(--signal)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 1200,
      margin: '0 auto',
      padding: '96px 48px'
    }
  }, /*#__PURE__*/React.createElement("h2", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 44,
      letterSpacing: '0.02em',
      margin: '0 0 32px',
      color: 'var(--text-strong)'
    }
  }, "LID\xC9 ZA VELOCITY"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(4,1fr)',
      gap: 20
    }
  }, people.map(p => /*#__PURE__*/React.createElement("div", {
    key: p.n
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      aspectRatio: '1',
      background: `url(${ASSETS}/team/${p.img}) center top / cover`,
      borderRadius: 'var(--radius-sm)'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-body)',
      fontWeight: 600,
      fontSize: 15,
      marginTop: 12,
      color: 'var(--text-strong)'
    }
  }, p.n), /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 13,
      color: 'var(--text-muted)'
    }
  }, p.r))))));
}
function ContactForm() {
  const [sent, setSent] = React.useState(false);
  const [agree, setAgree] = React.useState(true);
  return /*#__PURE__*/React.createElement("section", {
    id: "contact",
    style: {
      background: 'var(--white)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 1200,
      margin: '0 auto',
      padding: '96px 48px',
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 64
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 12,
      fontWeight: 600,
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      marginBottom: 8
    }
  }, "Ozv\u011Bte se"), /*#__PURE__*/React.createElement("h2", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 52,
      lineHeight: 0.9,
      letterSpacing: '0.02em',
      margin: '0 0 20px',
      color: 'var(--text-strong)'
    }
  }, "ZA\u010CN\u011ATE", /*#__PURE__*/React.createElement("br", null), "GEN Z AUDITEM"), /*#__PURE__*/React.createElement("p", {
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 16,
      lineHeight: 1.6,
      color: 'var(--text-muted)',
      maxWidth: 420
    }
  }, "Vypl\u0148te formul\xE1\u0159 a do 48 hodin se v\xE1m ozveme s prvn\xEDm pohledem na v\xE1\u0161 obsah."), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8,
      flexWrap: 'wrap',
      marginTop: 24
    }
  }, /*#__PURE__*/React.createElement(Tag, null, "Fashion & Streetwear"), /*#__PURE__*/React.createElement(Tag, null, "Beauty"), /*#__PURE__*/React.createElement(Tag, null, "Suplementy"), /*#__PURE__*/React.createElement(Tag, null, "Craft Food"))), /*#__PURE__*/React.createElement("div", null, sent ? /*#__PURE__*/React.createElement(Alert, {
    tone: "success",
    title: "\xDAsp\u011B\u0161n\u011B odesl\xE1no",
    style: {
      marginBottom: 16
    }
  }, "Ozveme se v\xE1m do 48 hodin.") : null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 20,
      opacity: sent ? 0.5 : 1
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 16
    }
  }, /*#__PURE__*/React.createElement(Input, {
    label: "Jm\xE9no",
    required: true,
    placeholder: "Jan"
  }), /*#__PURE__*/React.createElement(Input, {
    label: "P\u0159\xEDjmen\xED",
    required: true,
    placeholder: "Nov\xE1k"
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 16
    }
  }, /*#__PURE__*/React.createElement(Input, {
    label: "E-mail",
    required: true,
    type: "email",
    placeholder: "mail@firma.cz"
  }), /*#__PURE__*/React.createElement(Input, {
    label: "Telefon",
    type: "tel",
    placeholder: "+420 123 456 789"
  })), /*#__PURE__*/React.createElement(Select, {
    label: "Odv\u011Btv\xED",
    required: true
  }, /*#__PURE__*/React.createElement("option", {
    value: ""
  }, "Vyberte\u2026"), /*#__PURE__*/React.createElement("option", null, "Fashion & Streetwear"), /*#__PURE__*/React.createElement("option", null, "Beauty & Personal Care"), /*#__PURE__*/React.createElement("option", null, "Suplementy & sportovn\xED v\xFD\u017Eiva"), /*#__PURE__*/React.createElement("option", null, "Lifestyle & Wellness")), /*#__PURE__*/React.createElement(Checkbox, {
    label: "Souhlas\xEDm s poskytnut\xEDm osobn\xEDch \xFAdaj\u016F",
    checked: agree,
    onChange: e => setAgree(e.target.checked)
  }), /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    onClick: () => setSent(true),
    disabled: !agree
  }, "Odeslat popt\xE1vku")))));
}
function SiteFooter() {
  return /*#__PURE__*/React.createElement("footer", {
    style: {
      background: 'var(--bedrock)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 1200,
      margin: '0 auto',
      padding: '64px 48px 40px'
    }
  }, /*#__PURE__*/React.createElement(Banner, {
    title: "Objednejte si Gen Z Audit",
    subtitle: "Diagnostika obsahu, v\xFDstup je Gen Z sk\xF3re.",
    action: /*#__PURE__*/React.createElement(Button, {
      variant: "primary"
    }, "Objednat"),
    style: {
      marginBottom: 48
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingTop: 32,
      borderTop: '1px solid var(--border-inverse)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 12
    }
  }, /*#__PURE__*/React.createElement(Logo, {
    variant: "white",
    size: 28
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 22,
      letterSpacing: '0.06em',
      color: 'var(--signal)'
    }
  }, "VELOCITY")), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-body)',
      fontSize: 13,
      color: 'var(--text-muted-inverse)'
    }
  }, "\xA9 2026 Velocity \u2014 marketingov\xE1 & kreativn\xED agentura"))));
}
function VelocitySite() {
  const scrollToContact = () => {
    const el = document.getElementById('contact');
    if (el) window.scrollTo({
      top: el.offsetTop - 60,
      behavior: 'smooth'
    });
  };
  return /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-body)'
    }
  }, /*#__PURE__*/React.createElement(SiteHeader, {
    onCta: scrollToContact
  }), /*#__PURE__*/React.createElement(Hero, {
    onCta: scrollToContact
  }), /*#__PURE__*/React.createElement(Services, null), /*#__PURE__*/React.createElement(TeamStrip, null), /*#__PURE__*/React.createElement(ContactForm, null), /*#__PURE__*/React.createElement(SiteFooter, null));
}
Object.assign(window, {
  VelocitySite,
  SiteHeader,
  Hero,
  Services,
  TeamStrip,
  ContactForm,
  SiteFooter
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/Site.jsx", error: String((e && e.message) || e) }); }

__ds_ns.Banner = __ds_scope.Banner;

__ds_ns.Logo = __ds_scope.Logo;

__ds_ns.Badge = __ds_scope.Badge;

__ds_ns.Card = __ds_scope.Card;

__ds_ns.Tag = __ds_scope.Tag;

__ds_ns.Alert = __ds_scope.Alert;

__ds_ns.Tooltip = __ds_scope.Tooltip;

__ds_ns.Button = __ds_scope.Button;

__ds_ns.Checkbox = __ds_scope.Checkbox;

__ds_ns.IconButton = __ds_scope.IconButton;

__ds_ns.Input = __ds_scope.Input;

__ds_ns.Radio = __ds_scope.Radio;

__ds_ns.Select = __ds_scope.Select;

__ds_ns.Switch = __ds_scope.Switch;

__ds_ns.Tabs = __ds_scope.Tabs;

})();
