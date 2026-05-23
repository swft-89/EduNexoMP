<?php
session_start();
require_once __DIR__ . '/includes/carreras.php';
require_once __DIR__ . '/includes/csrf.php';

$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
$authModal = $_SESSION['auth_modal'] ?? null;
$mostrarSplash = empty($success) && empty($error);

unset($_SESSION['success'], $_SESSION['error'], $_SESSION['auth_modal']);
?>

<?php include 'includes/header.php'; ?>
<?php if ($mostrarSplash): ?>
    <?php include 'includes/splash.php'; ?>
<?php endif; ?>

<main>
    <section class="hero" id="inicio">
        <div class="container hero-content">
            <div class="hero-badge">Plataforma oficial ITCJ</div>

            <h1>Bienvenidos a EduNexo MP</h1>

            <p>
                La plataforma que conecta estudiantes con oportunidades reales
                en empresas e instituciones
            </p>

            <div class="hero-buttons">
                <button class="btn btn-primary" id="openLoginModal">Iniciar sesión</button>
                <button class="btn btn-outline" id="openRegisterModal">Registrarse</button>
            </div>
        </div>
    </section>

    <section class="benefits" id="beneficios">
        <div class="container">
            <p class="section-label" data-reveal>BENEFICIOS</p>
            <h2 data-reveal>¿Por qué EduNexo MP?</h2>
            <p class="section-description">
                Una plataforma diseñada para transformar la vinculación académica y profesional
            </p>

            <div class="benefits-grid" data-reveal>
                <div class="benefit-card">
                    <div class="benefit-icon">◎</div>
                    <h3>Matching inteligente</h3>
                    <p>
                        Algoritmo avanzado que conecta estudiantes con proyectos según sus
                        habilidades, intereses y perfil académico.
                    </p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">◌</div>
                    <h3>Vinculación con organizaciones</h3>
                    <p>
                        Conecta directamente con empresas, ONGs y organizaciones que buscan
                        soluciones innovadoras de estudiantes talentosos.
                    </p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon green">▣</div>
                    <h3>Seguimiento y evidencias</h3>
                    <p>
                        Sistema completo de gestión de propuestas, entregas y seguimiento
                        del progreso de los proyectos.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="steps" id="como-funciona">
    <div class="container">
        <div class="steps-card" data-reveal>
            <p class="section-label" >PROCESO SIMPLE</p>
            <h2>¿Cómo funciona?</h2>

            <div class="steps-grid">
                <div class="step-card" data-reveal>
                    <div class="step-circle">1</div>
                    <h3>Regístrate</h3>
                    <p>Crea tu cuenta como estudiante u organización en minutos</p>
                </div>

                <div class="step-card" data-reveal>
                    <div class="step-circle">2</div>
                    <h3>Conecta</h3>
                    <p>Explora desafíos o publica oportunidades según tu rol</p>
                </div>

                <div class="step-card" data-reveal>
                    <div class="step-circle">3</div>
                    <h3>Colabora</h3>
                    <p>Postula, evalúa propuestas y comunícate vía chat integrado</p>
                </div>
            </div>
            </div>
        </div>
    </section>

    <section class="contact-preview" id="contacto">
        <div class="container">
            <h2>¿Tienes preguntas?</h2>
            <p>Contáctanos y te ayudaremos a empezar con EduNexo MP</p>
        </div>
    </section>
</main>

<!-- MODAL LOGIN -->
<div class="auth-overlay" id="loginModal">
    <div class="auth-card">
        <button class="auth-close" id="closeLoginModal" type="button">×</button>

        <div class="auth-icon">
            <i class="bi bi-mortarboard"></i>
        </div>

        <h2 class="auth-title">Bienvenido a EduNexo MP</h2>
        <p class="auth-subtitle">Inicia sesión para continuar</p>

        <form class="auth-form" method="POST" action="procesos/login.php">
            <?php echo edunexo_csrf_input(); ?>
            <div class="auth-group">
                <label for="login_email">Email <span class="required-mark" aria-hidden="true">*</span></label>
                <input
                    type="email"
                    id="login_email"
                    name="correo"
                    placeholder="tu@email.com"
                    required
                >
            </div>

            <div class="auth-group">
                <label for="login_password">Contraseña <span class="required-mark" aria-hidden="true">*</span></label>
                <div class="password-field">
                    <input
                    type="password"
                    id="login_password"
                    name="contrasena"
                    placeholder="••••••••"
                    required
                >
                    <button type="button" class="password-toggle" data-password-toggle="login_password" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <label class="auth-check">
                <input type="checkbox" name="recordarme">
                <span>Recordarme</span>
            </label>

            <button type="submit" class="auth-submit">Iniciar sesión</button>

            <a href="recuperar_password.php" class="auth-link">¿Olvidaste tu contraseña?</a>

            <p class="auth-switch">
                ¿No tienes cuenta?
                <button type="button" class="auth-switch-btn" id="switchToRegister">
                    Regístrate
                </button>
            </p>
        </form>
    </div>
</div>

<!-- MODAL REGISTRO -->
<div class="auth-overlay" id="registerModal">
    <div class="register-card">
        <button class="auth-close" id="closeRegisterModal" type="button">×</button>

        <h2 class="register-title">Crear cuenta en EduNexo MP</h2>
        <p class="register-subtitle">Selecciona tu tipo de cuenta</p>

        <div class="role-tabs">
            <button type="button" class="role-tab active" data-role="estudiante">
                <i class="bi bi-mortarboard"></i>
                <span>Estudiante</span>
            </button>

            <button type="button" class="role-tab" data-role="organizacion">
                <i class="bi bi-building"></i>
                <span>Organización</span>
            </button>

            <button type="button" class="role-tab" data-role="administrador">
                <i class="bi bi-shield"></i>
                <span>Administrador</span>
            </button>
        </div>

        <form class="register-form" method="POST" action="procesos/registro.php">
            <?php echo edunexo_csrf_input(); ?>
            <input type="hidden" name="rol" id="rolSeleccionado" value="estudiante">

            <!-- ================= ESTUDIANTE ================= -->
            <div class="role-panel active" id="panel-estudiante">
                <div class="form-grid-2">
                    <div class="auth-group">
                        <label for="est_nombre">Nombre(s) <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="text" id="est_nombre" name="est_nombre" placeholder="Juan Carlos">
                    </div>

                    <div class="auth-group">
                        <label for="est_apellido_paterno">Apellido paterno <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="text" id="est_apellido_paterno" name="est_apellido_paterno" placeholder="Pérez">
                    </div>

                    <div class="auth-group">
                        <label for="est_apellido_materno">Apellido materno</label>
                        <input type="text" id="est_apellido_materno" name="est_apellido_materno" placeholder="González">
                    </div>

                    <div class="auth-group">
                        <label for="est_no_control">Número de control <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="text" id="est_no_control" name="est_no_control" placeholder="23111420">
                    </div>

                    <div class="auth-group">
                        <label for="est_carrera">Carrera <span class="required-mark" aria-hidden="true">*</span></label>
                        <select id="est_carrera" name="est_carrera">
                            <option value="">Selecciona tu carrera</option>
                            <?php foreach (edunexo_carreras_estudiante() as $carreraOpcion): ?>
                                <option value="<?php echo htmlspecialchars($carreraOpcion); ?>">
                                    <?php echo htmlspecialchars($carreraOpcion); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="<?php echo htmlspecialchars(edunexo_carrera_otra_value()); ?>">Otra</option>
                        </select>
                    </div>

                    <div class="auth-group is-hidden" id="est_carrera_otra_group">
                        <label for="est_carrera_otra">Especifica tu carrera <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="text" id="est_carrera_otra" name="est_carrera_otra" maxlength="120" placeholder="Escribe tu carrera">
                    </div>

                    <div class="auth-group">
                        <label for="est_semestre">Semestre <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="number" id="est_semestre" name="est_semestre" placeholder="8">
                    </div>

                    <div class="auth-group full">
                        <label for="est_intereses">Intereses</label>
                        <textarea id="est_intereses" name="est_intereses" maxlength="1000" placeholder="An&aacute;lisis de datos, desarrollo web, inteligencia artificial"></textarea>
                    </div>

                    <div class="auth-group">
                        <label for="est_curp">CURP</label>
                        <input type="text" id="est_curp" name="est_curp" placeholder="PEGJ040815HCHRRNA9">
                    </div>

                    <div class="auth-group">
                        <label for="est_telefono">Teléfono</label>
                        <input type="text" id="est_telefono" name="est_telefono" placeholder="6561234567">
                    </div>
                </div>

                <div class="register-section">
                    <h3>Ubicación</h3>

                    <div class="form-grid-2">
                        <div class="auth-group">
                            <label for="est_pais">País</label>
                            <input type="text" id="est_pais" name="est_pais" placeholder="México">
                        </div>

                        <div class="auth-group">
                            <label for="est_estado">Estado</label>
                            <input type="text" id="est_estado" name="est_estado" placeholder="Chihuahua">
                        </div>

                        <div class="auth-group">
                            <label for="est_ciudad">Ciudad</label>
                            <input type="text" id="est_ciudad" name="est_ciudad" placeholder="Cd. Juárez">
                        </div>

                        <div class="auth-group">
                            <label for="est_colonia">Colonia</label>
                            <input type="text" id="est_colonia" name="est_colonia" placeholder="Misión de los Lagos">
                        </div>

                        <div class="auth-group">
                            <label for="est_codigo_postal">Código postal</label>
                            <input type="text" id="est_codigo_postal" name="est_codigo_postal" placeholder="32575">
                        </div>

                        <div class="auth-group">
                            <label for="est_calle">Calle</label>
                            <input type="text" id="est_calle" name="est_calle" placeholder="Calle principal">
                        </div>

                        <div class="auth-group">
                            <label for="est_num_exterior">Número exterior</label>
                            <input type="text" id="est_num_exterior" name="est_num_exterior" placeholder="123">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= ORGANIZACION ================= -->
            <div class="role-panel" id="panel-organizacion">
                <div class="form-grid-2">
                    <div class="auth-group">
                        <label for="org_nombre_empresa">Nombre de la empresa <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="text" id="org_nombre_empresa" name="org_nombre_empresa" placeholder="Empresa ejemplo">
                    </div>

                    <div class="auth-group">
                        <label for="org_rfc">RFC</label>
                        <input type="text" id="org_rfc" name="org_rfc" placeholder="XAXX010101000">
                    </div>

                    <div class="auth-group">
                        <label for="org_sector">Sector</label>
                        <input type="text" id="org_sector" name="org_sector" placeholder="Tecnología">
                    </div>

                    <div class="auth-group">
                        <label for="org_representante">Representante</label>
                        <input type="text" id="org_representante" name="org_representante" placeholder="Nombre del representante">
                    </div>

                    <div class="auth-group">
                        <label for="org_telefono_contacto">Teléfono de contacto</label>
                        <input type="text" id="org_telefono_contacto" name="org_telefono_contacto" placeholder="6561234567">
                    </div>
                </div>

                <div class="register-section">
                    <h3>Ubicación <span class="optional-note">(opcional)</span></h3>

                    <div class="form-grid-2">
                        <div class="auth-group">
                            <label for="org_pais">País</label>
                            <input type="text" id="org_pais" name="org_pais" placeholder="México">
                        </div>

                        <div class="auth-group">
                            <label for="org_estado">Estado</label>
                            <input type="text" id="org_estado" name="org_estado" placeholder="Chihuahua">
                        </div>

                        <div class="auth-group">
                            <label for="org_ciudad">Ciudad</label>
                            <input type="text" id="org_ciudad" name="org_ciudad" placeholder="Cd. Juárez">
                        </div>

                        <div class="auth-group">
                            <label for="org_colonia">Colonia</label>
                            <input type="text" id="org_colonia" name="org_colonia" placeholder="Zona Centro">
                        </div>

                        <div class="auth-group">
                            <label for="org_codigo_postal">Código postal</label>
                            <input type="text" id="org_codigo_postal" name="org_codigo_postal" placeholder="32000">
                        </div>

                        <div class="auth-group">
                            <label for="org_calle">Calle</label>
                            <input type="text" id="org_calle" name="org_calle" placeholder="Av. Principal">
                        </div>

                        <div class="auth-group">
                            <label for="org_num_exterior">Número exterior</label>
                            <input type="text" id="org_num_exterior" name="org_num_exterior" placeholder="456">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= ADMINISTRADOR ================= -->
            <div class="role-panel" id="panel-administrador">
                <div class="form-grid-2">
                    <div class="auth-group">
                        <label for="adm_nombre">Nombre(s) <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="text" id="adm_nombre" name="adm_nombre" placeholder="Nombre">
                    </div>

                    <div class="auth-group">
                        <label for="adm_apellido_paterno">Apellido paterno <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="text" id="adm_apellido_paterno" name="adm_apellido_paterno" placeholder="Apellido paterno">
                    </div>

                    <div class="auth-group">
                        <label for="adm_apellido_materno">Apellido materno</label>
                        <input type="text" id="adm_apellido_materno" name="adm_apellido_materno" placeholder="Apellido materno">
                    </div>

                    <div class="auth-group">
                        <label for="adm_puesto">Puesto</label>
                        <input type="text" id="adm_puesto" name="adm_puesto" placeholder="Administrador del sistema">
                    </div>

                    <div class="auth-group">
                        <label for="adm_departamento">Departamento</label>
                        <input type="text" id="adm_departamento" name="adm_departamento" placeholder="Vinculación">
                    </div>
                </div>
            </div>

            <!-- ================= DATOS DE ACCESO ================= -->
            <div class="register-section">
                <h3>Datos de acceso</h3>

                <div class="form-grid-2">
                    <div class="auth-group">
                        <label for="correo">Correo electrónico <span class="required-mark" aria-hidden="true">*</span></label>
                        <input type="email" id="correo" name="correo" placeholder="nombre@correo.com" required>
                    </div>

                    <div class="auth-group">
                        <label for="contrasena">Contraseña <span class="required-mark" aria-hidden="true">*</span></label>
                        <div class="password-field">
                            <input type="password" id="contrasena" name="contrasena" placeholder="Crea una contraseña" required>
                            <button type="button" class="password-toggle" data-password-toggle="contrasena" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="auth-submit">Crear cuenta</button>

            <p class="auth-switch">
                ¿Ya tienes cuenta?
                <button type="button" class="auth-switch-btn" id="switchToLogin">
                    Inicia sesión
                </button>
            </p>
        </form>
    </div>
</div>

<?php if ($success): ?>
<script>
    window.edunexoSuccess = <?php echo json_encode($success); ?>;
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
    window.edunexoError = <?php echo json_encode($error); ?>;
    window.edunexoAuthModal = <?php echo json_encode($authModal); ?>;
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
