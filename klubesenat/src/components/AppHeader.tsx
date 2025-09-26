import { Settings, User, LogOut } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useNavigate } from "react-router-dom";
import { useAuth } from "@/hooks/useAuth";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import sestSenatLogo from "@/assets/sest-senat-logo.jpg";

interface AppHeaderProps {
  showBackButton?: boolean;
  title?: string;
}

export function AppHeader({ showBackButton = false, title }: AppHeaderProps) {
  const navigate = useNavigate();
  const { user, logout } = useAuth();

  return (
    <header className="bg-gradient-primary p-4 pb-8">
      {/* Logo SEST SENAT centralizada no topo */}
      <div className="flex justify-center mb-4">
        <img
          src={sestSenatLogo}
          alt="SEST SENAT - CNT | SEST SENAT | ITL Sistema Transporte"
          className="h-16 object-contain"
        />
      </div>

      {/* Header com navegação */}
      <div className="flex items-center justify-between max-w-md mx-auto">
        <div className="flex items-center space-x-2">
          {showBackButton && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => navigate(-1)}
              className="text-primary-foreground hover:bg-primary-light/20 p-2"
            >
              ←
            </Button>
          )}
          {/* Saudação ao usuário */}
          <div className="text-sm font-medium text-primary-foreground">
            {user ? `Olá, ${user.nome.split(' ')[0]}` : 'SestSenat Portal'}
          </div>
        </div>

        {!showBackButton && (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                size="sm"
                className="text-primary-foreground hover:bg-primary-light/20 p-2"
              >
                <Settings className="h-6 w-6" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
              <DropdownMenuItem onClick={() => navigate('/profile')}>
                <User className="mr-2 h-4 w-4" />
                Perfil
              </DropdownMenuItem>
              <DropdownMenuItem onClick={logout} className="text-red-600">
                <LogOut className="mr-2 h-4 w-4" />
                Sair
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        )}
      </div>
    </header>
  );
}