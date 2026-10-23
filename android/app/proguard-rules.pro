# Conservar puente JNI de Rust (TlatoaEngine) y modelos de datos
-keep class org.tlatoa.core.** { *; }
-keep class org.tlatoa.data.** { *; }
-keepclasseswithmembernames class * {
    native <methods>;
}
