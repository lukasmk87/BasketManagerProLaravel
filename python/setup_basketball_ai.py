#!/usr/bin/env python3
"""
Setup script for Basketball AI Video Analysis Environment
This script sets up the Python environment and dependencies for basketball video analysis.
"""

import os
import sys
import subprocess
import json
from pathlib import Path

def check_python_version():
    """Check if Python version is suitable for the project."""
    if sys.version_info < (3, 8):
        print("❌ Python 3.8 or higher is required")
        print(f"Current version: {sys.version}")
        return False
    
    print(f"✅ Python version: {sys.version_info.major}.{sys.version_info.minor}.{sys.version_info.micro}")
    return True

def check_system_dependencies():
    """Check for required system dependencies."""
    dependencies = {
        'ffmpeg': 'ffmpeg -version',
        'cmake': 'cmake --version',  # Required for some OpenCV builds
    }
    
    missing = []
    
    for dep, check_cmd in dependencies.items():
        try:
            result = subprocess.run(check_cmd.split(), 
                                  capture_output=True, 
                                  text=True, 
                                  timeout=10)
            if result.returncode == 0:
                print(f"✅ {dep} is installed")
            else:
                missing.append(dep)
        except (subprocess.TimeoutExpired, FileNotFoundError):
            missing.append(dep)
    
    if missing:
        print(f"❌ Missing system dependencies: {', '.join(missing)}")
        print("\nInstallation instructions:")
        print("Ubuntu/Debian: sudo apt-get install ffmpeg cmake")
        print("macOS: brew install ffmpeg cmake")
        print("Windows: Install via chocolatey or manually")
        return False
    
    return True

def create_virtual_environment():
    """Create Python virtual environment for the project."""
    venv_path = Path(__file__).parent / 'venv'
    
    if venv_path.exists():
        print(f"✅ Virtual environment already exists at {venv_path}")
        return venv_path
    
    try:
        print(f"📦 Creating virtual environment at {venv_path}")
        subprocess.run([sys.executable, '-m', 'venv', str(venv_path)], check=True)
        print("✅ Virtual environment created successfully")
        return venv_path
    except subprocess.CalledProcessError as e:
        print(f"❌ Failed to create virtual environment: {e}")
        return None

def get_pip_executable(venv_path):
    """Get the pip executable path for the virtual environment."""
    if os.name == 'nt':  # Windows
        return venv_path / 'Scripts' / 'pip'
    else:  # Unix-like
        return venv_path / 'bin' / 'pip'

def install_requirements(venv_path):
    """Install Python requirements in the virtual environment."""
    requirements_file = Path(__file__).parent / 'requirements.txt'
    pip_executable = get_pip_executable(venv_path)
    
    if not requirements_file.exists():
        print(f"❌ Requirements file not found: {requirements_file}")
        return False
    
    try:
        print("📦 Installing Python packages...")
        print("This may take several minutes, especially for OpenCV and ML libraries...")
        
        # Upgrade pip first
        subprocess.run([str(pip_executable), 'install', '--upgrade', 'pip'], check=True)
        
        # Install requirements
        subprocess.run([
            str(pip_executable), 'install', '-r', str(requirements_file)
        ], check=True)
        
        print("✅ All packages installed successfully")
        return True
        
    except subprocess.CalledProcessError as e:
        print(f"❌ Failed to install requirements: {e}")
        print("\nTry installing packages individually if this fails:")
        print("1. pip install opencv-python")
        print("2. pip install numpy scikit-learn")
        print("3. pip install -r requirements.txt")
        return False

def create_directory_structure():
    """Create necessary directory structure for basketball AI."""
    base_path = Path(__file__).parent
    
    directories = [
        'basketball_ai',
        'models',
        'models/pretrained',
        'models/custom',
        'data',
        'data/templates',
        'data/court_templates',
        'temp',
        'logs',
        'tests',
        'config'
    ]
    
    for directory in directories:
        dir_path = base_path / directory
        dir_path.mkdir(parents=True, exist_ok=True)
        print(f"📁 Created directory: {directory}")
    
    return True

def create_configuration_files():
    """Create default configuration files."""
    base_path = Path(__file__).parent
    
    # Create main config file
    config = {
        "analysis": {
            "default_confidence_threshold": 0.7,
            "max_players_per_frame": 12,
            "frame_analysis_interval": 30,
            "supported_video_formats": ["mp4", "avi", "mov", "mkv"]
        },
        "court_detection": {
            "enabled": True,
            "confidence_threshold": 0.8,
            "court_templates": "data/court_templates/"
        },
        "player_detection": {
            "enabled": True,
            "tracking_enabled": True,
            "jersey_number_ocr": False,
            "team_color_analysis": True
        },
        "action_recognition": {
            "enabled": True,
            "supported_actions": ["shot", "pass", "dribble", "rebound", "steal", "block"],
            "confidence_threshold": 0.75
        },
        "shot_analysis": {
            "enabled": True,
            "trajectory_analysis": True,
            "outcome_detection": True,
            "release_angle_analysis": True
        },
        "performance": {
            "max_frames_per_analysis": 200,
            "parallel_processing": True,
            "gpu_acceleration": False,
            "memory_limit_mb": 2048
        },
        "logging": {
            "level": "INFO",
            "log_file": "logs/basketball_ai.log",
            "max_log_size_mb": 100
        }
    }
    
    config_file = base_path / 'config' / 'basketball_ai_config.json'
    with open(config_file, 'w') as f:
        json.dump(config, f, indent=2)
    
    print(f"📝 Created configuration file: {config_file}")
    
    # Create .env template
    env_template = """# Basketball AI Environment Variables
BASKETBALL_AI_CONFIG_PATH=config/basketball_ai_config.json
BASKETBALL_AI_LOG_LEVEL=INFO
BASKETBALL_AI_TEMP_DIR=temp/
BASKETBALL_AI_MODEL_DIR=models/
BASKETBALL_AI_GPU_ENABLED=false

# Laravel Integration
LARAVEL_STORAGE_PATH=/path/to/laravel/storage/app/
LARAVEL_LOG_CHANNEL=basketball_ai

# Optional: External API Keys
# SPORTS_DATA_API_KEY=your_api_key_here
# BASKETBALL_REFERENCE_API_KEY=your_api_key_here
"""
    
    env_file = base_path / '.env.example'
    with open(env_file, 'w') as f:
        f.write(env_template)
    
    print(f"📝 Created environment template: {env_file}")
    
    return True

def test_installation():
    """Test the installation by running basic imports."""
    test_script = """
import sys
try:
    import cv2
    print(f"✅ OpenCV version: {cv2.__version__}")
    
    import numpy as np
    print(f"✅ NumPy version: {np.__version__}")
    
    import sklearn
    print(f"✅ Scikit-learn version: {sklearn.__version__}")
    
    # Test basic OpenCV functionality
    img = np.zeros((100, 100, 3), dtype=np.uint8)
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    print("✅ Basic OpenCV operations work")
    
    print("\\n🎉 All core dependencies are working correctly!")
    
except ImportError as e:
    print(f"❌ Import error: {e}")
    sys.exit(1)
except Exception as e:
    print(f"❌ Error testing installation: {e}")
    sys.exit(1)
"""
    
    try:
        venv_path = Path(__file__).parent / 'venv'
        if os.name == 'nt':  # Windows
            python_executable = venv_path / 'Scripts' / 'python'
        else:  # Unix-like
            python_executable = venv_path / 'bin' / 'python'
        
        result = subprocess.run([str(python_executable), '-c', test_script], 
                              capture_output=True, text=True, timeout=30)
        
        if result.returncode == 0:
            print(result.stdout)
            return True
        else:
            print(f"❌ Installation test failed:")
            print(result.stderr)
            return False
            
    except Exception as e:
        print(f"❌ Failed to test installation: {e}")
        return False

def create_usage_examples():
    """Create example usage scripts."""
    base_path = Path(__file__).parent
    
    example_script = '''#!/usr/bin/env python3
"""
Example usage of Basketball AI Video Analysis
"""

import json
import sys
from pathlib import Path

# Add the basketball_ai directory to path
sys.path.append(str(Path(__file__).parent / 'basketball_ai'))

from comprehensive_analysis import BasketballVideoAnalyzer

def analyze_video_example():
    """Example of how to analyze a video file."""
    
    # Example input data (same format as Laravel will send)
    input_data = {
        "video_id": "example_video_001",
        "video_path": "path/to/your/video.mp4",
        "analysis_type": "comprehensive_game_analysis",
        "video_metadata": {
            "duration": 3600,  # seconds
            "width": 1920,
            "height": 1080,
            "fps": 30
        },
        "capabilities": {
            "player_detection": {"enabled": True, "confidence_threshold": 0.7},
            "court_detection": {"enabled": True, "confidence_threshold": 0.8},
            "action_recognition": {"enabled": True, "confidence_threshold": 0.75},
            "shot_analysis": {"enabled": True}
        }
    }
    
    # Initialize analyzer
    analyzer = BasketballVideoAnalyzer()
    
    # Perform analysis
    results = analyzer.analyze_video(input_data)
    
    # Print results
    print("Analysis Results:")
    print(f"Overall Confidence: {results.get('overall_confidence', 'N/A')}")
    print(f"Players Detected: {len(results.get('players_detected', []))}")
    print(f"Actions Recognized: {len(results.get('actions_recognized', []))}")
    print(f"Shots Analyzed: {len(results.get('shots_analyzed', []))}")
    
    return results

if __name__ == "__main__":
    print("🏀 Basketball AI Video Analysis Example")
    print("=" * 50)
    
    # Note: Replace with actual video path for testing
    print("⚠️  To test with real video, update the video_path in the script")
    
    try:
        results = analyze_video_example()
        print("\\n✅ Example completed successfully!")
        
        # Save results to file
        with open("example_results.json", "w") as f:
            json.dump(results, f, indent=2)
        print("📁 Results saved to example_results.json")
        
    except Exception as e:
        print(f"❌ Example failed: {e}")
        sys.exit(1)
'''
    
    example_file = base_path / 'example_analysis.py'
    with open(example_file, 'w') as f:
        f.write(example_script)
    
    # Make it executable
    os.chmod(example_file, 0o755)
    
    print(f"📝 Created example script: {example_file}")
    return True

def main():
    """Main setup function."""
    print("🏀 Basketball AI Video Analysis Setup")
    print("=" * 50)
    
    # Check requirements
    if not check_python_version():
        sys.exit(1)
    
    if not check_system_dependencies():
        print("\n⚠️  Some system dependencies are missing.")
        print("The setup will continue, but you may encounter issues.")
        response = input("Continue anyway? (y/n): ")
        if response.lower() != 'y':
            sys.exit(1)
    
    # Create virtual environment
    venv_path = create_virtual_environment()
    if not venv_path:
        sys.exit(1)
    
    # Create directory structure
    if not create_directory_structure():
        sys.exit(1)
    
    # Install requirements
    if not install_requirements(venv_path):
        print("\n⚠️  Package installation failed.")
        print("You can try installing manually or continue with limited functionality.")
        response = input("Continue anyway? (y/n): ")
        if response.lower() != 'y':
            sys.exit(1)
    
    # Create configuration files
    if not create_configuration_files():
        print("⚠️  Failed to create configuration files")
    
    # Create usage examples
    if not create_usage_examples():
        print("⚠️  Failed to create example scripts")
    
    # Test installation
    print("\n🧪 Testing installation...")
    if test_installation():
        print("\n🎉 Setup completed successfully!")
        
        print("\nNext steps:")
        print("1. Activate virtual environment:")
        
        if os.name == 'nt':  # Windows
            print(f"   {venv_path}\\Scripts\\activate")
        else:  # Unix-like
            print(f"   source {venv_path}/bin/activate")
        
        print("2. Test with example:")
        print("   python example_analysis.py")
        
        print("3. Configure settings in:")
        print("   config/basketball_ai_config.json")
        
        print("4. Copy and customize:")
        print("   cp .env.example .env")
        
    else:
        print("\n❌ Setup completed with warnings.")
        print("Some components may not work correctly.")
        print("Check the error messages above and install missing dependencies.")

if __name__ == "__main__":
    main()