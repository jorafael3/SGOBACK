# Códigos de Error JWT - Sistema SGO

## Descripción General

Este documento describe los códigos de error HTTP y respuestas JSON que el backend envía cuando hay problemas con la autenticación JWT, para que Angular pueda manejarlos apropiadamente.

## Códigos HTTP para Autenticación

### 🔴 401 - Unauthorized
**Uso**: Problemas de autenticación/autorización  
**Acción Angular**: Cerrar sesión automáticamente y redirigir al login

### 🔴 405 - Method Not Allowed  
**Uso**: Método HTTP incorrecto  
**Acción Angular**: Verificar configuración del HTTP request

## Tipos de Errores JWT

### 1. Token Faltante
```json
{
  "success": false,
  "error": "Token no proporcionado",
  "error_code": "TOKEN_MISSING",
  "requires_login": true
}
```
**HTTP Code**: 401  
**Angular Action**: Redirigir al login

### 2. Token Expirado 🎯
```json
{
  "success": false,
  "error": "Token expirado",
  "error_code": "TOKEN_EXPIRED",
  "requires_login": true,
  "session_expired": true,
  "expired_at": "2025-10-21 14:30:00"
}
```
**HTTP Code**: 401  
**Angular Action**: Mostrar mensaje "Sesión expirada" y redirigir al login

### 3. Token con Firma Inválida
```json
{
  "success": false,
  "error": "Firma del token inválida",
  "error_code": "TOKEN_INVALID_SIGNATURE", 
  "requires_login": true,
  "invalid_token": true
}
```
**HTTP Code**: 401  
**Angular Action**: Limpiar localStorage y redirigir al login

### 4. Token Malformado
```json
{
  "success": false,
  "error": "Formato de token inválido",
  "error_code": "TOKEN_MALFORMED",
  "requires_login": true,
  "invalid_token": true
}
```
**HTTP Code**: 401  
**Angular Action**: Limpiar localStorage y redirigir al login

### 5. Error de Decodificación
```json
{
  "success": false,
  "error": "Error al decodificar token: [detalle]",
  "error_code": "TOKEN_DECODE_ERROR",
  "requires_login": true,
  "invalid_token": true
}
```
**HTTP Code**: 401  
**Angular Action**: Limpiar localStorage y redirigir al login

## Endpoints para Verificación

### 1. Verificar Estado del Token
```
GET/POST /api/PrepararFacturas/CheckTokenStatus
```

**Headers**: 
```
Authorization: Bearer [token]
```

**Respuesta Exitosa**:
```json
{
  "success": true,
  "token_valid": true,
  "empresa": "produccion_cartimex",
  "usuario": "admin",
  "expires_at": "2025-10-21 18:00:00"
}
```

**Respuesta de Error** (ejemplo token expirado):
```json
{
  "success": false,
  "token_valid": false,
  "error": "Token expirado",
  "error_code": "TOKEN_EXPIRED",
  "requires_login": true,
  "session_expired": true,
  "expired_at": "2025-10-21 14:30:00"
}
```

### 2. Verificar Empresa Actual
```
GET/POST /api/PrepararFacturas/CheckCurrentEmpresa
```

**Respuesta Exitosa**:
```json
{
  "success": true,
  "empresa_actual": "produccion_cartimex",
  "usuario_actual": "admin", 
  "token_valid": true
}
```

## Implementación en Angular

### 1. HTTP Interceptor para Manejo Automático

```typescript
// auth.interceptor.ts
import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpErrorResponse } from '@angular/common/http';
import { catchError } from 'rxjs/operators';
import { throwError } from 'rxjs';
import { Router } from '@angular/router';
import { AuthService } from './auth.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  
  constructor(
    private router: Router,
    private authService: AuthService
  ) {}

  intercept(req: HttpRequest<any>, next: HttpHandler) {
    // Agregar token si existe
    const token = localStorage.getItem('token');
    if (token) {
      req = req.clone({
        headers: req.headers.set('Authorization', `Bearer ${token}`)
      });
    }

    return next.handle(req).pipe(
      catchError((error: HttpErrorResponse) => {
        // Manejar errores de autenticación
        if (error.status === 401) {
          this.handleAuthError(error);
        }
        return throwError(error);
      })
    );
  }

  private handleAuthError(error: HttpErrorResponse) {
    const errorBody = error.error;
    
    if (errorBody && errorBody.requires_login) {
      // Limpiar sesión
      this.authService.logout();
      
      // Mostrar mensaje específico según el tipo de error
      if (errorBody.session_expired) {
        this.showMessage('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
      } else if (errorBody.invalid_token) {
        this.showMessage('Token de sesión inválido. Por favor, inicia sesión nuevamente.');
      } else {
        this.showMessage('Se requiere autenticación. Por favor, inicia sesión.');
      }
      
      // Redirigir al login
      this.router.navigate(['/login']);
    }
  }

  private showMessage(message: string) {
    // Implementar según tu sistema de notificaciones
    // Ejemplo: this.toastr.warning(message);
    console.warn(message);
  }
}
```

### 2. Servicio de Autenticación Mejorado

```typescript
// auth.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { BehaviorSubject, Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<any>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(
    private http: HttpClient,
    private router: Router
  ) {}

  // Verificar estado del token
  checkTokenStatus(): Observable<any> {
    return this.http.get('/api/PrepararFacturas/CheckTokenStatus');
  }

  // Logout completo
  logout() {
    // Limpiar almacenamiento
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('empresa');
    sessionStorage.clear();
    
    // Limpiar estado del servicio
    this.currentUserSubject.next(null);
    
    // No redirigir aquí si se llama desde el interceptor
    // El interceptor ya maneja la redirección
  }

  // Verificar si el usuario está autenticado
  isAuthenticated(): boolean {
    const token = localStorage.getItem('token');
    return !!token;
  }

  // Obtener empresa actual
  getCurrentEmpresa(): string | null {
    return localStorage.getItem('empresa');
  }
}
```

### 3. Guard para Rutas Protegidas

```typescript
// auth.guard.ts
import { Injectable } from '@angular/core';
import { CanActivate, Router } from '@angular/router';
import { AuthService } from './auth.service';
import { Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {
  
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate(): Observable<boolean> {
    if (!this.authService.isAuthenticated()) {
      this.router.navigate(['/login']);
      return of(false);
    }

    // Verificar que el token sigue siendo válido
    return this.authService.checkTokenStatus().pipe(
      map(response => {
        if (response.success && response.token_valid) {
          return true;
        } else {
          this.router.navigate(['/login']);
          return false;
        }
      }),
      catchError(() => {
        // Si hay error, asumir que el token no es válido
        this.router.navigate(['/login']);
        return of(false);
      })
    );
  }
}
```

### 4. Componente para Verificación Periódica

```typescript
// app.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { AuthService } from './services/auth.service';
import { interval, Subscription } from 'rxjs';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html'
})
export class AppComponent implements OnInit, OnDestroy {
  private tokenCheckSubscription: Subscription;

  constructor(private authService: AuthService) {}

  ngOnInit() {
    // Verificar token cada 5 minutos si el usuario está autenticado
    this.tokenCheckSubscription = interval(5 * 60 * 1000).subscribe(() => {
      if (this.authService.isAuthenticated()) {
        this.authService.checkTokenStatus().subscribe(
          response => {
            if (!response.success || !response.token_valid) {
              console.warn('Token ya no es válido');
              // El interceptor manejará el logout automático
            }
          },
          error => {
            console.warn('Error al verificar token:', error);
            // El interceptor manejará el error
          }
        );
      }
    });
  }

  ngOnDestroy() {
    if (this.tokenCheckSubscription) {
      this.tokenCheckSubscription.unsubscribe();
    }
  }
}
```

## Flujo de Manejo de Errores

### 1. Token Expirado
1. **Backend**: Detecta `exp < time()` → Retorna 401 con `TOKEN_EXPIRED`
2. **Angular Interceptor**: Detecta 401 + `session_expired: true` 
3. **Angular**: Muestra "Sesión expirada" → Limpia localStorage → Redirige al login

### 2. Token Inválido  
1. **Backend**: Detecta firma inválida → Retorna 401 con `TOKEN_INVALID_SIGNATURE`
2. **Angular Interceptor**: Detecta 401 + `invalid_token: true`
3. **Angular**: Muestra "Token inválido" → Limpia localStorage → Redirige al login

### 3. Sin Token
1. **Backend**: No encuentra Authorization header → Retorna 401 con `TOKEN_MISSING` 
2. **Angular Interceptor**: Detecta 401 + `requires_login: true`
3. **Angular**: Redirige directamente al login

## Testing

### Probar Token Expirado
```bash
# En el navegador, modificar el localStorage para simular token expirado
localStorage.setItem('token', 'token_expirado_simulado');
```

### Probar Sin Token
```bash
# Limpiar localStorage y hacer request
localStorage.removeItem('token');
```

### Usar Endpoints de Debug
```javascript
// En consola del navegador
fetch('/api/PrepararFacturas/CheckTokenStatus', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('token')
  }
})
.then(r => r.json())
.then(console.log);
```

## Códigos de Error Resumidos

| Error Code | HTTP | Descripción | Angular Action |
|------------|------|-------------|----------------|
| `TOKEN_MISSING` | 401 | Sin token | Redirigir login |
| `TOKEN_EXPIRED` | 401 | Token expirado | Mensaje + logout |
| `TOKEN_INVALID_SIGNATURE` | 401 | Firma inválida | Limpiar + login |
| `TOKEN_MALFORMED` | 401 | Formato incorrecto | Limpiar + login |
| `TOKEN_DECODE_ERROR` | 401 | Error decodificación | Limpiar + login |
| `METHOD_NOT_ALLOWED` | 405 | Método HTTP incorrecto | Verificar request |

## Notas Importantes

- **Siempre** verificar `requires_login: true` para decidir si hacer logout
- **Distinguir** entre `session_expired` e `invalid_token` para mensajes apropiados
- **Usar** el interceptor HTTP para manejo automático y consistente
- **Implementar** verificación periódica del token en segundo plano
- **Limpiar** completamente el localStorage en todos los casos de error de autenticación
